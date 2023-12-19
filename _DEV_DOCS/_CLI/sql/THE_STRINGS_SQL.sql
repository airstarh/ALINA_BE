-- CREATE INDEX DESC
CREATE INDEX audit_at_IDX USING BTREE ON audit (`at` DESC);

-- ALTER KEY
ALTER TABLE tale
    DROP INDEX IND_TALE_PUBLISH_AT;
ALTER TABLE tale
    DROP INDEX IND_TALE_CREATED_AT;
CREATE INDEX tale_publish_at_IDX USING BTREE ON tale (publish_at DESC);
ALTER TABLE watch_visit
    DROP INDEX IND_WV_VISITED_AT;
CREATE INDEX watch_visit_visited_at_IDX USING BTREE ON watch_visit (visited_at DESC);

-- https://stackoverflow.com/questions/986826/how-to-do-a-regular-expression-replace-in-mysql
select REGEXP_REPLACE("stackoverflow", "(stack)(over)(flow)", '$2 - $1 - $3');

ALTER TABLE `home.zero`.pm_department ADD CONSTRAINT pm_department_FK FOREIGN KEY (pm_organization_id) REFERENCES `home.zero`.pm_organization(id);

ALTER TABLE `home.zero`.`user` MODIFY COLUMN id BIGINT auto_increment NOT NULL;
