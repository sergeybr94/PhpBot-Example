.PHONY: up down restart install sh nginx-restart nginx-logs

up:
	docker-compose -p custom-bot -f docker-compose.yml  up -d
down:
	docker-compose -p custom-bot down

restart: down up

install:
	docker-compose -p custom-bot exec -w /var/www/ php composer install

sh:
	docker-compose -p custom-bot exec php bash

nginx-restart:
	docker-compose -p custom-bot restart nginx

nginx-logs:
	docker-compose -p custom-bot logs nginx