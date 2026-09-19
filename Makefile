
.PHONY: dev reset test
dev:
	docker compose up -d db

reset:
	docker compose exec -T db psql -U postgres -c 'DROP DATABASE IF EXISTS test;'
	docker compose exec -T db psql -U postgres -c 'CREATE DATABASE test;'
	docker compose exec -T db psql -U postgres -d test -c 'CREATE SCHEMA bookstore_schemas; CREATE SCHEMA contest; CREATE SCHEMA second_hand_books;'

test:
	docker compose build
	docker compose run --rm php
