<?php namespace Sugarcrm\Bean\Interfaces;

interface BeanInterface
{

    public function __construct(array $attributes = array());

    public static function all(
        $columns = array('*'),
        $limit = 1000,
        $offset = 0
    ); // we would need that into query builder at some point

    public function delete();

    public static function deleteBean($id);

    public static function get($columns = array('*'), $options = array(), $related = array(), $legacy = true);

    public static function find($id, $columns = array('*'));

    public function relate($id, $link, $fields = array());

    public function unrelate($id, $link);

    public static function related($id, $link, $returnClass, $options = array());

    public static function metadata();

    public static function lang();

    public function save(array $fields = array(), array $options = array());

    public function map(array $attributes);

    public static function on($connection = null);

    public function combineMetadata();

}

