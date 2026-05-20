# laravel-boleto — Constituição do Módulo

> SDK PHP para geração de boletos bancários compatível com Laravel 5-8. SDK externo — não modificar sem aprovação.

## Identidade
- Módulo: laravel-boleto — SDK de boletos para Laravel (`compufour/laravel-boleto`)
- Parte do monorepo: zweb-projects

## Stack
- **Linguagem:** PHP ≥5.5
- **Framework compatível:** Laravel 5.x, 6.x, 7.x, 8.x
- **Dependências principais:** `setasign/fpdf`, `neitanod/forceutf8`
- **Testes:** PHPUnit 6/8/9 + Orchestra Testbench
- **Gerenciador:** Composer

## Estrutura de pastas
```
laravel-boleto/
├── src/           # Código-fonte do SDK (PSR-4: Eduardokum\LaravelBoleto)
├── tests/         # Testes PHPUnit
├── exemplos/      # Exemplos de uso
├── logos/         # Logos dos bancos
├── manuais/       # Documentação dos bancos
└── docs/          # Documentação geral
```

## Comandos do projeto
```bash
# Instalar dependências
composer install

# Executar testes
vendor/bin/phpunit

# Testes com cobertura
vendor/bin/phpunit --coverage-text
```

## Restrições
- SDK externo (fork do compufour/laravel-boleto) — modificações exigem aprovação explícita
- Compatibilidade multi-versão Laravel deve ser mantida
- Dados de boleto (beneficiário, valor, CNPJ) nunca em logs
