Upgrade notes for ProxyStatistics rciam-2.2
======================================
* MySQL: upgrade from rciam-2.1 to rciam-2.2:
    - `CREATE TABLE statistics_ip (
    accessed TIMESTAMP NOT NULL,
    sourceIdp VARCHAR(255) NOT NULL,
    service VARCHAR(255) NOT NULL,
    user VARCHAR(255) NOT NULL,
    ip VARBINARY(16) NOT NULL,
    ipVersion VARCHAR(4) NOT NULL,
    INDEX (accessed),
    INDEX (sourceIdp),
    INDEX (service),
    INDEX (user),
    INDEX (ipVersion),
    PRIMARY KEY (accessed, sourceIdp, service, user, ip, ipVersion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;`
 * POSTGRES: upgrade from rciam-2.1 to rciam-2.2:
    - `CREATE TABLE statistics_ip (
    accessed timestamptz NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    userid character varying(255) NOT NULL,
    ip CIDR NOT NULL,
    ipversion VARCHAR(4) NOT NULL,
    PRIMARY KEY (accessed, sourceidp, service, userid, ip, ipversion)
    );`
       `CREATE INDEX statistics_ip_i1 ON statistics_ip (accessed);`
       `CREATE INDEX statistics_ip_i2 ON statistics_ip (sourceidp);`
       `CREATE INDEX statistics_ip_i3 ON statistics_ip (service);`
       `CREATE INDEX statistics_ip_i4 ON statistics_ip (userid);`
       `CREATE INDEX statistics_ip_i5 ON statistics_ip (ipversion);`