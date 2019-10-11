<?php

class IvvyCurlTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildPostField()
    {
        // Arrange
        $postData = array(
            'single' => array(
                'name' => 'single_name',
            ),
            'nested' => array(
                'name' => array(
                    0 => 'nested_one',
                    'root' => 'nested_root',
                    '1' => array(
                        '0' => 'nested_two'
                    ),
                    '2' => array(
                        'parent' => 'nested_parent'
                    ),
                    '3' => array(
                        '0' => array(
                            '0' => 'nested_three'
                        )
                    ),
                    '4' => array(
                        '0' => array(
                            'child' => 'nested_child'
                        )
                    ),
                )
            )
        );
        $ivvyCurl = new \IvvyEvent\CurlClient(null);

        // Act
        $output = array();
        $ivvyCurl->buildPostField($postData, $output);

        // Assert
        $expected = array(
            'single[name]' => 'single_name',
            'nested[name][0]' => 'nested_one',
            'nested[name][root]' => 'nested_root',
            'nested[name][1][0]' => 'nested_two',
            'nested[name][2][parent]' => 'nested_parent',
            'nested[name][3][0][0]' => 'nested_three',
            'nested[name][4][0][child]' => 'nested_child',
        );
        $this->assertEquals($expected, $output);
    }

    public function testGetFormattedFiles()
    {
        // Arrange
        $files = array(
            'single' => array(
                'name' => 'single.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phpApO28i',
                'error' => 0,
                'size' => 3441,
            ),
            'nested' => array(
                'name' => array(
                    0 => 'nested_one.txt',
                    'root' => 'nested_root.txt',
                    '1' => array(
                        '0' => 'nested_two.txt'
                    ),
                    '2' => array(
                        'parent' => 'nested_parent.txt'
                    ),
                    '3' => array(
                        '0' => array(
                            '0' => 'nested_three.txt'
                        )
                    ),
                    '4' => array(
                        '0' => array(
                            'child' => 'nested_child.txt'
                        )
                    ),
                ),
                'tmp_name' => array(
                    0 => '/tmp/single',
                    'root' => '/tmp/nested_root.txt',
                    '1' => array(
                        '0' => '/tmp/nested_two.txt'
                    ),
                    '2' => array(
                        'parent' => '/tmp/nested_parent.txt'
                    ),
                    '3' => array(
                        '0' => array(
                            '0' => '/tmp/nested_three.txt'
                        )
                    ),
                    '4' => array(
                        '0' => array(
                            'child' => '/tmp/nested_child.txt'
                        )
                    ),
                ),
                'type' => array(
                    0 => 'image/jpegFortext',
                    'root' => 'image/jpegFornested_root.txt',
                    '1' => array(
                        '0' => 'image/jpegFornested_two.txt'
                    ),
                    '2' => array(
                        'parent' => 'image/jpegFornested_parent.txt'
                    ),
                    '3' => array(
                        '0' => array(
                            '0' => 'image/jpegFornested_three.txt'
                        )
                    ),
                    '4' => array(
                        '0' => array(
                            'child' => 'image/jpegFornested_child.txt'
                        )
                    ),
                ),
                'size' => array(
                    0 => '100',
                    'root' => '200',
                    '1' => array(
                        '0' => '300'
                    ),
                    '2' => array(
                        'parent' => '400'
                    ),
                    '3' => array(
                        '0' => array(
                            '0' => '500'
                        )
                    ),
                    '4' => array(
                        '0' => array(
                            'child' => '600'
                        )
                    ),
                ),
            )
        );

        $ivvyCurl = new \IvvyEvent\CurlClient(null);

        // Act
        $formattedFiles = $ivvyCurl->getFormattedFiles($files);

        // Assert
        $expected = array(
            'single' => array(
                'name' => 'single.txt',
                'tmp_name' => '/tmp/phpApO28i',
                'type' => 'text/plain',
                'size' => 3441,
            ),
            'nested[0]' => array(
                'name' => 'nested_one.txt',
                'tmp_name' => '/tmp/single',
                'type' => 'image/jpegFortext',
                'size' => '100',
            ),
            'nested[root]' => array(
                'name' => 'nested_root.txt',
                'tmp_name' => '/tmp/nested_root.txt',
                'type' => 'image/jpegFornested_root.txt',
                'size' => '200',
            ),
            'nested[1][0]' => array(
                'name' => 'nested_two.txt',
                'tmp_name' => '/tmp/nested_two.txt',
                'type' => 'image/jpegFornested_two.txt',
                'size' => '300',
            ),
            'nested[2][parent]' => array(
                'name' => 'nested_parent.txt',
                'tmp_name' => '/tmp/nested_parent.txt',
                'type' => 'image/jpegFornested_parent.txt',
                'size' => '400',
            ),
            'nested[3][0][0]' => array(
                'name' => 'nested_three.txt',
                'tmp_name' => '/tmp/nested_three.txt',
                'type' => 'image/jpegFornested_three.txt',
                'size' => '500',
            ),
            'nested[4][0][child]' => array(
                'name' => 'nested_child.txt',
                'tmp_name' => '/tmp/nested_child.txt',
                'type' => 'image/jpegFornested_child.txt',
                'size' => '600',
            ),
        );
        $this->assertEquals($expected, $formattedFiles);
    }
}
