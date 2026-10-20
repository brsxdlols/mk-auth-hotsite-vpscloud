"""Keep the template readable through MK-Auth's legacy HTML conversion."""
from html import unescape
from pathlib import Path

template = Path(__file__).resolve().parents[1] / 'theme/layout/vpscloud/index.html'
source = template.read_text(encoding='utf-8')
assert source.isascii(), 'Use HTML entities for template accents and symbols'
visible = unescape(source)
for expected in ('Olá, assinante!', 'Atendimento e suporte', 'Consultar faturas →',
                 'Consulte suas faturas →', '☰', '◎', '▤', '©',
                 'selecione a fatura que deseja consultar.'):
    assert expected in visible, expected
assert not any(0x80 <= ord(c) <= 0x9f for c in visible)
for encoding in ('latin-1', 'cp1252'):
    assert unescape(source.encode('utf-8').decode(encoding)) == visible
print('PASS template text and symbols survive legacy Latin-1/Windows-1252 conversion')
