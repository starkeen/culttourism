FROM mariadb:10.3
# mariadb ignores world-writable config files
RUN chmod o-w /etc/mysql/conf.d/*.cnf

#HEALTHCHECK --interval=5s --timeout=3s --retries=3 \
#  CMD ["mysqladmin", "ping"]
