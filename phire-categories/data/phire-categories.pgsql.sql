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
  "title" varchar(255) NOT NULL,
  "uri" varchar(255) NOT NULL,
  "slug" varchar(255),
  "order" integer,
  "order_by_field" varchar,
  "order_by_order" varchar,
  "filter" integer,
  "pagination" integer,
  "hierarchy" varchar(255),
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_category_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

ALTER SEQUENCE category_id_seq OWNED BY "[{prefix}]categories"."id";
CREATE INDEX "category_parent_id" ON "[{prefix}]categories" ("parent_id");
CREATE INDEX "category_title" ON "[{prefix}]categories" ("title");
CREATE INDEX "category_uri" ON "[{prefix}]categories" ("uri");
CREATE INDEX "category_slug" ON "[{prefix}]categories" ("slug");
CREATE INDEX "category_order" ON "[{prefix}]categories" ("order");

-- --------------------------------------------------------

--
-- Table structure for table "category_items"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]category_items" (
  "category_id" integer NOT NULL,
  "content_id" integer,
  "media_id" integer,
  "order" integer,
  UNIQUE ("category_id", "content_id", "media_id"),
  CONSTRAINT "fk_category_item_id" FOREIGN KEY ("category_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_category_content_id" FOREIGN KEY ("content_id") REFERENCES "[{prefix}]content" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "fk_category_media_id" FOREIGN KEY ("media_id") REFERENCES "[{prefix}]media" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "category_item_id" ON "[{prefix}]content_to_categories" ("category_id");
CREATE INDEX "category_content_id" ON "[{prefix}]content_to_categories" ("content_id");
CREATE INDEX "category_media_id" ON "[{prefix}]content_to_categories" ("media_id");