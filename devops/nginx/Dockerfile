# devops/nginx/Dockerfile
FROM nginx:alpine

# (opcional) Copia defaults p/ a imagem — úteis se você subir sem volumes
COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d/ /etc/nginx/conf.d/
COPY html/ /usr/share/nginx/html/


# Testa sintaxe (falha o build se tiver erro)
RUN nginx -t
