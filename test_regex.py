import re
text = """    #[ORM\Column(name: 'nom', length: 100)]
    private ?string $nom = null;"""
def repl(m):
    return "MATCH: " + m.group(1) + " AND " + m.group(2)
print(re.sub(r'(#\[ORM\\Column[^\]]*\])\s*(private\s+\?[^;]+;)', repl, text))
