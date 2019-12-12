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
CREATE INDEX statistics_i3 ON statistics (year);
CREATE INDEX statistics_i4 ON statistics (year,month);
CREATE INDEX statistics_i5 ON statistics (year,month,day);

--Tables for mapping identifier to name
CREATE TABLE identityprovidersmap (
    entityid character varying(255) NOT NULL,
    name character varying(255) NOT NULL
);

CREATE TABLE serviceprovidersmap (
    identifier character varying(255) NOT NULL,
    name character varying(255) NOT NULL
);