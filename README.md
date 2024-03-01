
## Projeto criado para aprendizado de integração com Mercado Pago

## Instalando o projeto

O projeto se utiliza de contêineres Docker, através do pacote *Laravel Sail* para facilitar a configuração do ambiente de desenvolvimento. Portanto, é necessário que já possua o Docker e o Docker Compose instalados na máquina.

Links para instalação e configuração de Docker:

- [Windows](https://docs.docker.com/docker-for-windows/install/)
- [Linux (Debian based)](https://docs.docker.com/engine/install/ubuntu/)

### Passos para o rodar o projeto localmente

- Faça um clone do projeto para sua máquina local
- Crie um arquivo `.env`, recomendamos usar `.env-example` como base
- Adicione ou altere as chaves conforme sua necessidade
- acesse a pasta do projeto via console (terminal/PowerShell/CMD)
- execute o comando:

```shell
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
 ```

- Após finalizado processamento, execute o comando `./sail up -d`
- Rode ```sail artisan key:generate```
- Rode ```npm install```
- Rode ```sail artisan migrate --seed``` para criar os dados fictícios de teste.

Após esses passos rodar:

- ```sail npm run dev``` para iniciar o vite.
- ```sail artisan queue:work``` para realizar os envios de e-mail através das filas.

O primeiro comando realiza a instalação dos pacotes via composer especificados no arquivo `composer.json` e uma vez que a instalação termina, a pasta *vendor* passa a ficar disponível no projeto. O comando seguinte levanta os contêineres baseado na descrição de serviços feita no arquivo `docker-compose.yml`.

Por padrão, não é necessária nenhuma configuração no arquivo *.env* do projeto. Caso seja necessária alguma edição na configuração padrão (relacionado a binding ports ou credenciais de banco de dados), basta editar o arquivo *.env*.
