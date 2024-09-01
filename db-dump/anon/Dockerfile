FROM myanon as anon
FROM mysql:8

COPY --from=anon /bin/myanon /bin/myanon

WORKDIR /app
COPY entrypoint.sh anon.conf ./

ENTRYPOINT ["/app/entrypoint.sh"]