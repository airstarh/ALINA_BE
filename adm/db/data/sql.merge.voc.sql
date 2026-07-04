INSERT INTO alina.voc (`from`, en_US, ru_RU)
SELECT s.`from`, s.en_US, s.ru_RU
FROM stage.voc AS s
ON DUPLICATE KEY UPDATE
    en_US = COALESCE(alina.voc.en_US, s.en_US),
    ru_RU = COALESCE(alina.voc.ru_RU, s.ru_RU);
