--
-- Categories Module MySQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]categories` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `parent_id` int(16),
  `title` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `slug` varchar(255),
  `order` int(16),
  `order_by_field` varchar(255),
  `order_by_order` varchar(255),
  `filter` int(1),
  `pagination` int(16),
  `hierarchy` varchar(255),
  PRIMARY KEY (`id`),
  INDEX `category_parent_id` (`parent_id`),
  INDEX `category_title` (`title`),
  INDEX `category_uri` (`uri`),
  INDEX `category_slug` (`slug`),
  INDEX `category_order` (`order`),
  CONSTRAINT `fk_category_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11001;

-- --------------------------------------------------------

--
-- Table structure for table `category_items`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]category_items` (
  `category_id` integer NOT NULL,
  `content_id` integer,
  `media_id` integer,
  `order` integer,
  INDEX `category_item_id` (`category_id`),
  INDEX `category_content_id` (`content_id`),
  INDEX `category_media_id` (`media_id`),
  UNIQUE (`category_id`, `content_id`, `media_id`),
  CONSTRAINT `fk_category_item_id` FOREIGN KEY (`category_id`) REFERENCES `[{prefix}]categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_category_content_id` FOREIGN KEY (`content_id`) REFERENCES `[{prefix}]content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_category_media_id` FOREIGN KEY (`media_id`) REFERENCES `[{prefix}]media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
