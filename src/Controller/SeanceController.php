<?php

namespace App\Controller;
use App\Entity\Seance;
use App\Repository\ReservationRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SeanceController extends AbstractController
{

private function dateOccupee($repo,$dateDebut,$dateFin,$ignoreId=null)
{
$seances=$repo->findAll();

foreach($seances as $s){

if($ignoreId && $s->getId()==$ignoreId)
continue;

if(
($dateDebut >= $s->getDateDebut() && $dateDebut < $s->getDateFin()) ||
($dateFin > $s->getDateDebut() && $dateFin <= $s->getDateFin()) ||
($dateDebut <= $s->getDateDebut() && $dateFin >= $s->getDateFin())
){
return true;
}
}

return false;
}

#[Route('/seances', name: 'seances')]
public function indexClient(
Request $request,
SeanceRepository $repo,
ReservationRepository $reservationRepo,
SessionInterface $session
): Response {

$search = $request->query->get('search','');
$filtre = $request->query->get('filtre','toutes');

if($search){
$seances = $repo->findBySearch($search);
}else{
$seances = $repo->findAll();
}

$placesRestantes = [];
$dejaReserve = [];

$userId = $session->get('user_id');

foreach($seances as $s){

$reservations = $reservationRepo->findBy([
'idSeance'=>$s->getId()
]);

$reservationsActives = array_filter(
$reservations,
fn($r)=>$r->getStatut()!='annulée'
);

$placesRestantes[$s->getId()] =
$s->getCapacite() - count($reservationsActives);

$dejaReserve[$s->getId()] = false;

foreach($reservationsActives as $r){

if(method_exists($r,'getUser') && $r->getUser() == $userId){
$dejaReserve[$s->getId()] = true;
}

if(method_exists($r,'getIdUser') && $r->getIdUser() == $userId){
$dejaReserve[$s->getId()] = true;
}

if(method_exists($r,'getUserId') && $r->getUserId() == $userId){
$dejaReserve[$s->getId()] = true;
}

}

}

return $this->render('client/seance_index.html.twig', [
'seances' => $seances,
'search' => $search,
'filtre' => $filtre,
'placesRestantes' => $placesRestantes,
'dejaReserve' => $dejaReserve
]);
}

#[Route('/admin/seances', name:'admin_seances')]
public function adminIndex(
Request $request,
SeanceRepository $repo,
ReservationRepository $reservationRepo,
SessionInterface $session
):Response{

if($session->get('user_role')!=='admin')
return $this->redirectToRoute('login');

$tri=$request->query->get('tri','date');
$search=$request->query->get('search','');

if($search){
$seances=$repo->findBySearch($search);
}
elseif($tri==='capacite_desc'){
$seances=$repo->findAllOrderedByCapacite('DESC');
}
elseif($tri==='capacite_asc'){
$seances=$repo->findAllOrderedByCapacite('ASC');
}
else{
$seances=$repo->findAll();
}

$statsReservations=[];

foreach($seances as $s){

$reservationsActives=array_filter(
$reservationRepo->findBy(['idSeance'=>$s->getId()]),
fn($r)=>$r->getStatut()!='annulée'
);

$statsReservations[$s->getId()]=[
'nb'=>count($reservationsActives),
'taux'=>$s->getCapacite()>0
? round((count($reservationsActives)/$s->getCapacite())*100)
:0
];
}

return $this->render('admin/seance.html.twig',[
'seances'=>$seances,
'statsReservations'=>$statsReservations,
'tri'=>$tri,
'search'=>$search,
'editId'=>null,
'errorsEdit'=>[],
'errors'=>[],
'submitted'=>false,
    'google_api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? '',
    'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL']


]);
}



#[Route('/admin/seances/new', name: 'admin_seance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {

        $seance = new Seance();

        $seance->setTitre((string)$request->request->get('titre', ''));
        $seance->setLieu((string)$request->request->get('lieu', ''));
        $seance->setDescription((string)$request->request->get('description', ''));
        $seance->setCapacite((int)$request->request->get('capacite', 0));
        $seance->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        $seance->setDateFin(new \DateTime($request->request->get('dateFin')));

        // ✔️ ASSERT VALIDATION ONLY
        $violations = $validator->validate($seance);

        $errors = [];
        foreach ($violations as $v) {
            $errors[$v->getPropertyPath()] = $v->getMessage();
        }

        // logique métier (date)
        if ($seance->getDateFin() <= $seance->getDateDebut()) {
            $errors['dateFin'] = "Date fin doit être après début";
        }

        if (!empty($errors)) {
            return $this->render('admin/seance.html.twig', [
                'seances' => $em->getRepository(Seance::class)->findAll(),
                'errors' => $errors,
                'editId' => null,
                'errorsEdit' => [],
                'submitted' => true,
                'statsReservations' => [],
                'tri' => 'date',
                'search' => ''
            ]);
        }

        $em->persist($seance);
        $em->flush();

        return $this->redirectToRoute('admin_seances');
    }


#[Route('/admin/seances/edit/{id}', name:'admin_seance_edit', methods:['POST','GET'])]
public function edit(
    Request $request,
    Seance $seance,
    
    EntityManagerInterface $em,
    ValidatorInterface $validator
): Response {

    // ======================
    // 1. UPDATE ENTITY
    // ======================
    $seance->setTitre((string)$request->request->get('titre', ''));
    $seance->setLieu((string)$request->request->get('lieu', ''));
    $seance->setDescription((string)$request->request->get('description', ''));
    $seance->setCapacite((int)$request->request->get('capacite', 0));

    $dateDebut = new \DateTime($request->request->get('dateDebut'));
    $dateFin   = new \DateTime($request->request->get('dateFin'));

    $seance->setDateDebut($dateDebut);
    $seance->setDateFin($dateFin);

    // ======================
    // 2. VALIDATION SYMFONY
    // ======================
    $violations = $validator->validate($seance);

    $errorsEdit = [];

    foreach ($violations as $v) {
        $field = $v->getPropertyPath();
        $errorsEdit[$field] = $v->getMessage();
    }

    // ======================
    // 3. BUSINESS RULE
    // ======================
    if ($dateFin <= $dateDebut) {
        $errorsEdit['dateFin'] = "La date de fin doit être après la date de début";
    }

    // ======================
    // 4. IF ERRORS → RENDER PAGE
    // ======================
    if (!empty($errorsEdit)) {
        return $this->render('admin/seance.html.twig', [
            'seances' => $em->getRepository(Seance::class)->findAll(),

            // important pour ton twig
            'errorsEdit' => $errorsEdit,
            'editId' => $seance->getId(),

            // IMPORTANT (évite erreur tri/search dans twig)
            'tri' => 'date',
            'search' => '',
            'errors' => [],
            'submitted' => true,
            'statsReservations' => [],
        ]);
    }

    // ======================
    // 5. CHECK DATE OCCUPÉE
    // ======================
    if ($this->dateOccupee(
        $em->getRepository(Seance::class),
        $dateDebut,
        $dateFin,
        $seance->getId()
    )) {
        $this->addFlash('error', "Date déjà utilisée");
        return $this->redirectToRoute('admin_seances');
    }

    // ======================
    // 6. SAVE
    // ======================
    $em->flush();

    $this->addFlash('success', "Séance modifiée avec succès");

    return $this->redirectToRoute('admin_seances');
}



#[Route('/admin/seances/delete/{id}',name:'admin_seance_delete')]
public function delete(Seance $seance,EntityManagerInterface $em):Response{

$em->remove($seance);
$em->flush();

$this->addFlash('success',"Séance supprimée avec succès");

return $this->redirectToRoute('admin_seances');
}

}