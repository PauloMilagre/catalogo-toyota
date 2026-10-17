#!/usr/bin/env bash
#
# Testes de segurança contra o site RODANDO (complementa os testes PHPUnit).
# Faz requisições HTTP de verdade, com cookies, como um atacante faria.
#
# Uso (no Git Bash, com o XAMPP ligado):
#   bash bin/testes-seguranca.sh http://localhost/catalogo-toyota
#
# Cria uma conta de teste com e-mail aleatório. Não testa o limite de
# tentativas de login de propósito: isso bloquearia o seu próprio IP por
# 15 minutos. Esse caso está coberto em tests/Integracao/AutenticacaoTest.php.

BASE="${1:-http://localhost/catalogo-toyota}"
PASTA="$(mktemp -d)"
trap 'rm -rf "$PASTA"' EXIT
EMAIL="seguranca-$RANDOM$RANDOM@teste.com"
SENHA="senha1234"
FALHAS=0

titulo() { printf '\n== %s\n' "$1"; }

# confere STATUS_ESPERADO STATUS_OBTIDO DESCRICAO
confere() {
  if [ "$1" = "$2" ]; then
    printf '  [OK]    %s -> HTTP %s\n' "$3" "$2"
  else
    printf '  [FALHA] %s -> esperado %s, veio %s\n' "$3" "$1" "$2"
    FALHAS=$((FALHAS + 1))
  fi
}

# status METODO URL [opções extras do curl...]
status() {
  local metodo="$1" url="$2"; shift 2
  curl -s -o "$PASTA/corpo" -w '%{http_code}' -X "$metodo" "$@" "$BASE$url"
}

json() { status "$1" "$2" -H 'Content-Type: application/json' "${@:3}"; }

echo "Testes de segurança em $BASE"
echo "Data: $(date '+%Y-%m-%d %H:%M:%S')"

titulo "1. Acesso sem login"
confere 401 "$(status GET /api/agendamentos)" "GET /api/agendamentos sem sessão"
confere 303 "$(status GET /meus-agendamentos)" "GET /meus-agendamentos sem sessão (vai para o login)"

titulo "Preparação: conta de cliente $EMAIL"
confere 201 "$(json POST /api/auth/registro -d "{\"nome\":\"Teste Seguranca\",\"email\":\"$EMAIL\",\"senha\":\"$SENHA\"}")" "cadastro"
confere 200 "$(json POST /api/auth/login -c "$PASTA/cliente" -d "{\"email\":\"$EMAIL\",\"senha\":\"$SENHA\"}")" "login"
TOKEN="$(sed -n 's/.*"csrf_token":"\([0-9a-f]*\)".*/\1/p' "$PASTA/corpo")"

titulo "2. SQL Injection"
confere 422 "$(json POST /api/auth/login -d "{\"email\":\"' OR '1'='1' -- \",\"senha\":\"x\"}")" "e-mail \"' OR '1'='1' -- \" no login"
confere 401 "$(json POST /api/auth/login -d "{\"email\":\"$EMAIL\",\"senha\":\"' OR '1'='1\"}")" "senha \"' OR '1'='1\" no login"
confere 404 "$(status GET '/api/agendamentos/1%20OR%201=1' -b "$PASTA/cliente")" "id \"1 OR 1=1\" na URL"
confere 422 "$(status GET "/api/agendamentos?status=pendente'%20OR%20'1'='1" -b "$PASTA/cliente")" "filtro status=\"pendente' OR '1'='1\""

titulo "3. XSS refletido"
curl -s -c "$PASTA/visitante" "$BASE/cadastro" > "$PASTA/form"
TOKEN_FORM="$(sed -n 's/.*name="_csrf" value="\([0-9a-f]*\)".*/\1/p' "$PASTA/form" | head -1)"
confere 422 "$(status POST /cadastro -b "$PASTA/visitante" --data-urlencode "_csrf=$TOKEN_FORM" \
  --data-urlencode 'nome=<script>alert("xss")</script>' --data-urlencode 'email=invalido' --data-urlencode 'senha=senha1234')" \
  "cadastro com nome <script>alert(\"xss\")</script>"
if grep -q '<script>alert' "$PASTA/corpo"; then
  printf '  [FALHA] o script voltou intacto na página\n'; FALHAS=$((FALHAS + 1))
else
  printf '  [OK]    a página devolveu o texto escapado: %s\n' "$(grep -o '&lt;script&gt;[^"]*' "$PASTA/corpo" | head -1)"
fi

titulo "4. CSRF"
confere 403 "$(json POST /api/agendamentos -b "$PASTA/cliente" -d '{"modelo_id":1,"data":"2030-01-07","periodo":"manha"}')" \
  "POST /api/agendamentos sem X-CSRF-Token"
confere 403 "$(status POST /agendar -b "$PASTA/cliente" -d 'modelo_id=1&data=2030-01-07&periodo=manha')" \
  "formulário /agendar sem o campo _csrf"
confere 403 "$(status POST /agendar -b "$PASTA/cliente" -d "modelo_id=1&data=2030-01-07&periodo=manha&_csrf=$TOKEN_FORM")" \
  "formulário /agendar com token de OUTRA sessão"

titulo "5. Perfil insuficiente (cliente tentando área de administrador)"
confere 403 "$(status GET /admin/agendamentos -b "$PASTA/cliente")" "GET /admin/agendamentos"
confere 403 "$(json POST /api/modelos -b "$PASTA/cliente" -H "X-CSRF-Token: $TOKEN" -d '{"slug":"invasor"}')" "POST /api/modelos"
confere 403 "$(status DELETE /api/modelos/1 -b "$PASTA/cliente" -H "X-CSRF-Token: $TOKEN")" "DELETE /api/modelos/1"

titulo "6. Sessão reutilizada depois do logout"
cp "$PASTA/cliente" "$PASTA/cookie-antigo"
confere 200 "$(status GET /api/auth/eu -b "$PASTA/cliente")" "antes do logout, a sessão vale"
confere 204 "$(json POST /api/auth/logout -b "$PASTA/cliente" -c "$PASTA/cliente" -H "X-CSRF-Token: $TOKEN")" "logout"
confere 401 "$(status GET /api/auth/eu -b "$PASTA/cookie-antigo")" "mesmo cookie de antes do logout"
confere 401 "$(json POST /api/agendamentos -b "$PASTA/cookie-antigo" -H "X-CSRF-Token: $TOKEN" -d '{"modelo_id":1,"data":"2030-01-07","periodo":"manha"}')" \
  "cookie e token antigos juntos"

echo
if [ "$FALHAS" -eq 0 ]; then
  echo "Resultado: todos os ataques foram barrados."
else
  echo "Resultado: $FALHAS verificação(ões) falharam."
fi
exit "$FALHAS"
