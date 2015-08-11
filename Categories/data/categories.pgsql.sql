--
-- Categories Module PostgreSQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

--
-- Table structure for table "categories"
--

CREATE SEQUENCE category_id_seq START 11001;

CREATE TABLE IF NOT EXISTS "[{prefix}]categories" (
  "id" integer NOT NULL DEFAULT nextval('category_id_seq'),
  "parent_id" integer,
  "name" varchar(255) NOT NULL,
  "uri" varchar(255) NOT NULL,
  "slug" varchar(255),
  "order" integer,
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_category_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

ALTER SEQUENCE category_id_seq OWNED BY "[{prefix}]categories"."id";
CREATE INDEX "category_parent_id" ON "[{prefix}]categories" ("parent_id");
CREATE INDEX "category_name" ON "[{prefix}]categories" ("name");
CREATE INDEX "category_uri" ON "[{prefix}]categories" ("uri");
CREATE INDEX "category_slug" ON "[{prefix}]categories" ("slug");
CREATE INDEX "category_order" ON "[{prefix}]categories" ("order");

-- --------------------------------------------------------

--
-- Table structure for table "content_to_categories"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content_to_categories" (
  "content_id" integer NOT NULL,
  "category_id" integer NOT NULL,
  "order" integer NOT NULL,
  "type" varchar(255) NOT NULL,
  UNIQUE ("content_id", "category_id"),
  CONSTRAINT "fk_content_category_id" FOREIGN KEY ("category_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "category_content_id" ON "[{prefix}]content_to_categories" ("content_id");
CREATE INDEX "content_category_id" ON "[{prefix}]content_to_categories" ("category_id");

