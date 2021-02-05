--Statistics for IdPs
CREATE TABLE statistics (
    year bigint NOT NULL,
    month bigint NOT NULL,
    day bigint NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    count bigint,
    PRIMARY KEY (year, month, day, sourceIdp, service)
);

CREATE INDEX statistics_i1 ON statistics (sourceIdp);
CREATE INDEX statistics_i2 ON statistics (service);

CREATE TABLE statistics_detail (
    year bigint NOT NULL,
    month bigint NOT NULL,
    day bigint NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    userid character varying(255) NOT NULL,
    count bigint,
    PRIMARY KEY (year, month, day, sourceIdp, service, userid)
);

CREATE INDEX statistics_detail_i1 ON statistics_detail (sourceIdp);
CREATE INDEX statistics_detail_i2 ON statistics_detail (service);
CREATE INDEX statistics_detail_i3 ON statistics_detail (userid);

CREATE TABLE statistics_ip (
    accessed timestamptz NOT NULL,
    sourceidp character varying(255) NOT NULL,
    service character varying(255) NOT NULL,
    userid character varying(255) NOT NULL,
    ip CIDR NOT NULL,
    ipversion VARCHAR(4) NOT NULL,
    PRIMARY KEY (accessed, sourceidp, service, userid, ip, ipversion)
);

CREATE INDEX statistics_ip_i1 ON statistics_ip (accessed);
CREATE INDEX statistics_ip_i2 ON statistics_ip (sourceidp);
CREATE INDEX statistics_ip_i3 ON statistics_ip (service);
CREATE INDEX statistics_ip_i4 ON statistics_ip (userid);
CREATE INDEX statistics_ip_i5 ON statistics_ip (ipversion);

--Tables for mapping identifier to name
CREATE TABLE identityprovidersmap (
    entityid character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    PRIMARY KEY (entityid)
);

CREATE TABLE serviceprovidersmap (
    identifier character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    PRIMARY KEY (identifier)
);
