--Statistics for IdPs
CREATE TABLE IF NOT EXISTS statistics (
    year bigint NOT NULL,
    month bigint NOT NULL,
    day bigint NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    count bigint,
    PRIMARY KEY (year, month, day, sourceIdp, service)
);

CREATE INDEX IF NOT EXISTS statistics_i1 ON statistics (sourceIdp);
CREATE INDEX IF NOT EXISTS statistics_i2 ON statistics (service);

CREATE TABLE IF NOT EXISTS statistics_detail (
    year bigint NOT NULL,
    month bigint NOT NULL,
    day bigint NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    userid character varying(255) NOT NULL,
    count bigint,
    PRIMARY KEY (year, month, day, sourceIdp, service, userid)
);

CREATE INDEX IF NOT EXISTS statistics_detail_i1 ON statistics (sourceIdp);
CREATE INDEX IF NOT EXISTS statistics_detail_i2 ON statistics (service);

CREATE TABLE IF NOT EXISTS statistics_ip (
    accessed timestamptz NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    userid character varying(255) NOT NULL,
    ip CIDR NOT NULL,
    ipversion VARCHAR(4) NOT NULL,
    PRIMARY KEY (accessed, sourceidp, service, userid, ip, ipversion)
);

CREATE INDEX IF NOT EXISTS statistics_ip_i1 ON statistics_ip (accessed);
CREATE INDEX IF NOT EXISTS statistics_ip_i2 ON statistics_ip (sourceidp);
CREATE INDEX IF NOT EXISTS statistics_ip_i3 ON statistics_ip (service);
CREATE INDEX IF NOT EXISTS statistics_ip_i4 ON statistics_ip (userid);
CREATE INDEX IF NOT EXISTS statistics_ip_i5 ON statistics_ip (ipversion);

--Tables for mapping identifier to name
CREATE TABLE IF NOT EXISTS identityprovidersmap (
    entityid character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    PRIMARY KEY (entityid)
);

CREATE TABLE IF NOT EXISTS serviceprovidersmap (
    identifier character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    PRIMARY KEY (identifier)
);
