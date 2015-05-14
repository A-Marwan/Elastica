<?php

namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Index;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class QueryStringTest extends BaseTest
{
    public function testSearchMultipleFields()
    {
        $str = md5(rand());
        $query = new QueryString($str);

        $expected = array(
            'query' => $str,
        );

        $this->assertEquals(array('query_string' => $expected), $query->toArray());

        $fields = array();
        $max = rand() % 10 + 1;
        for ($i = 0; $i <  $max; $i++) {
            $fields[] = md5(rand());
        }

        $query->setFields($fields);
        $expected['fields'] = $fields;
        $this->assertEquals(array('query_string' => $expected), $query->toArray());

        foreach (array(false, true) as $val) {
            $query->setUseDisMax($val);
            $expected['use_dis_max'] = $val;

            $this->assertEquals(array('query_string' => $expected), $query->toArray());
        }
    }

    public function testSearch()
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test');
        $index->create(array(), true);
        $index->getSettings()->setNumberOfReplicas(0);
        //$index->getSettings()->setNumberOfShards(1);

        $type = new Type($index, 'helloworld');

        $doc = new Document(1, array('email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5')));
        $type->addDocument($doc);

        // Refresh index
        $index->refresh();

        $queryString = new QueryString('test*');
        $resultSet = $type->search($queryString);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Tests if search in multiple fields is possible
     */
    public function testSearchFields()
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $doc = new Document(1, array('title' => 'hello world', 'firstname' => 'nicolas', 'lastname' => 'ruflin', 'price' => '102', 'year' => '2012'));
        $type->addDocument($doc);
        $index->refresh();

        $query = new QueryString();
        $query = $query->setQuery('ruf*');
        $query = $query->setDefaultField('title');
        $query = $query->setFields(array('title', 'firstname', 'lastname', 'price', 'year'));

        $resultSet = $type->search($query);
        $this->assertEquals(1, $resultSet->count());
    }

    public function testSetDefaultOperator()
    {
        $operator = 'AND';
        $query = new QueryString('test');
        $query->setDefaultOperator($operator);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['default_operator'], $operator);
    }

    public function testSetDefaultField()
    {
        $default = 'field1';
        $query = new QueryString('test');
        $query->setDefaultField($default);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['default_field'], $default);
    }

    public function testSetRewrite()
    {
        $rewrite = 'scoring_boolean';
        $query = new QueryString('test');
        $query->setRewrite($rewrite);

        $data = $query->toArray();

        $this->assertEquals($data['query_string']['rewrite'], $rewrite);
    }

    /**
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testSetQueryInvalid()
    {
        $query = new QueryString();
        $query->setQuery(array());
    }

    public function testSetTimezone()
    {
        $timezone = 'Europe/Paris';
        $text = 'date:[2012 TO 2014]';

        $query = new QueryString($text);
        $query->setTimezone($timezone);

        $expected = array(
            'query_string' => array(
                'query' => $text,
                'time_zone' => $timezone,
            ),
        );

        $this->assertEquals($expected, $query->toArray());
        $this->assertInstanceOf('Elastica\Query\QueryString', $query->setTimezone($timezone));
    }
}