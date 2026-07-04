INSERT INTO alina.voc (`from`, en_US, ru_RU)
SELECT s.`from`, s.en_US, s.ru_RU
FROM stage.voc AS s
ON DUPLICATE KEY UPDATE
    en_US = COALESCE(alina.voc.en_US, VALUES(en_US)),
    ru_RU = COALESCE(alina.voc.ru_RU, VALUES(ru_RU));
;

INSERT INTO stage.voc (`from`, en_US, ru_RU)
SELECT s.`from`, s.en_US, s.ru_RU
FROM alina.voc AS s
ON DUPLICATE KEY UPDATE
    en_US = COALESCE(stage.voc.en_US, VALUES(en_US)),
    ru_RU = COALESCE(stage.voc.ru_RU, VALUES(ru_RU))
;
