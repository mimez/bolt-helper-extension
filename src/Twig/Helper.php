<?php

namespace Bolt\Extension\MichaelMezger\Helper\Twig;

use Silex\Application;

class Helper
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param Application $app
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    public function __construct(Application $app)
    {
        $this->setApp($app);
    }


    public function getContentIdsByFilter($filter) {
        $conditions = array();

        // filter nach contenttype
        foreach (array('taxonomytype', 'contenttype', 'slug') as $field) {
            if (isset($filter[$field])) {
                if (!is_array($filter[$field])) {
                    $filter[$field] = array($filter[$field]);
                }
                $fieldConditions = array();
                foreach ($filter[$field] as $value) {
                    $fieldConditions[] = sprintf("%s = %s", $field, $this->app['db']->quote($value));
                }

                if (count($fieldConditions)) {
                    $conditions[$field] = '(' . implode(') OR (', $fieldConditions) . ')';
                }
            }
        }

        if (isset($filter['limit']['limit'])) {
            $limitSql = 'LIMIT ' . (int)$filter['limit']['limit'];
        }

        if (isset($filter['exclude'])) {
            $conditions['content_id'] = "content_id != " .  $this->app['db']->quote($filter['exclude']);
        }

        if (isset($filter['exclude_slug'])) {
            $excludeSlugs = is_array($filter['exclude_slug']) ? $filter['exclude_slug'] : array($filter['exclude_slug']);
            $fieldConditions = array();
            foreach ($excludeSlugs as $slug) {
                $fieldConditions[] = sprintf("slug != %s", $this->app['db']->quote($slug));
            }
            $conditions['exclude_slugs'] = '(' . implode(') AND (', $fieldConditions) . ')';
        }

        $conditions['published'] = "content_id IN (SELECT id FROM bolt_entries WHERE status = 'published')";

        $q = "SELECT
                content_id
              FROM
                bolt_taxonomy" .
            (count($conditions) ? ' WHERE (' . implode(') AND (', $conditions) . ')': '') .
            " ORDER BY content_id DESC " .
            (isset($limitSql) ? $limitSql : '');

        // filter nach taxonmytype
        $contents = $this->app['db']->fetchAll($q);

        $contentIds = array();
        foreach ($contents as $content) {
            $contentIds[] = $content['content_id'];
        }

        if (isset($filter['fill_with_random']) && $filter['fill_with_random']) {
            $conditions = array();
            if (isset($filter['limit']['limit'])) {
                $limitSql = 'LIMIT ' . ((int)$filter['limit']['limit'] - count($contentIds));
            }

            if (isset($filter['exclude'])) {
                $conditions['id'] = "id != " .  $this->app['db']->quote($filter['exclude']);
            }

            $q = "SELECT
                    id
                  FROM
                    bolt_entries
                  WHERE
                    id NOT IN ('" . (count($contentIds) ? implode("', '", $contentIds) : '0') . "')" .
                (count($conditions) ? ' AND (' . implode(') AND (', $conditions) . ')': '') .
                (isset($limitSql) ? $limitSql : '');

            $contents = $this->app['db']->fetchAll($q);

            foreach ($contents as $content) {
                $contentIds[] = $content['id'];
            }
        }

        return $contentIds;
    }

    public function implode($glue, $pieces)
    {
        return implode($glue, $pieces);
    }

    public function getActiveMenuIdentifier() {
        $url = $this->app['request']->getRequestUri();
        $url = ltrim($url, '/');

        if (strpos($url, 'hochzeit') !== false) {
            return 'wedding';
        }

        if (strpos($url, 'geburtstag') !== false) {
            return 'birthday';
        }

        if (strpos($url, 'geldscheine-falten') !== false) {
            return 'falten';
        }

        if (strpos($url, 'shop') !== false) {
            return 'shop';
        }

        if (strpos($url, 'ideen') !== false) {
            return 'ideen';
        }
    }

    public function fileGetContents($path)
    {
        return file_get_contents($path);
    }

    public function contify(\Twig_Environment $env, $string)
    {

        $string = new \Twig_Markup(preg_replace_callback('#entries\((.*)\)#Ui', function($matches) use ($env) {
            $options = (array)json_decode($matches[1]);
            $options['contenttype'] = 'entries';
            $contentIds = $this->getContentIdsByFilter($options);
            return $env->render('templates/helper/entries.twig', ['contentids' => $contentIds, 'type' => 'teaser']);
        }, $string), 'utf-8');

        return $string;
    }
}