start up:
	docker-compose up -d

stop down:
	docker-compose down

list:
	docker-compose ps

list-all:
	docker ps -a

# Exec containers
enter php exec exec-app:
	docker-compose exec linting_tools sh

# Logs
log logs:
	docker-compose logs linting_tools

tail follow:
	docker-compose logs --follow linting_tools
