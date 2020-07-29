<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrackableModelTest extends WebTestCase
{
    /**
     * @testdox Test that content is detected as HTML
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testHtmlIsDetectedInContent()
    {
        $mockRedirectModel       = $this->createMock(RedirectModel::class);
        $mockLeadFieldRepository = $this->createMock(LeadFieldRepository::class);

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([$mockRedirectModel, $mockLeadFieldRepository])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromHtml'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromHtml')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'html'),
            [],
            'email',
            1
        );
    }

    /**
     * @testdox Test that content is detected as plain text
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromText
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testPlainTextIsDetectedInContent()
    {
        $mockRedirectModel       = $this->createMock(RedirectModel::class);
        $mockLeadFieldRepository = $this->createMock(LeadFieldRepository::class);

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([$mockRedirectModel, $mockLeadFieldRepository])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromText'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromText')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'text'),
            [],
            'email',
            1
        );
    }

    /**
     * @testdox Test that a standard link with a standard query is parsed correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithStandardQuery()
    {
        $url   = 'https://foo-bar.com?foo=bar';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link without a query parses correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithoutQuery()
    {
        $url   = 'https://foo-bar.com';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link with a tokenized query parses correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithTokenizedQuery()
    {
        $url   = 'https://foo-bar.com?foo={contactfield=bar}&bar=foo';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=bar}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @testdox Test that a token used in place of a URL is parsed properly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedDomain()
    {
        $url   = 'http://{contactfield=foo}.org';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => 'mautic',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedHostWithScheme()
    {
        $url   = '{contactfield=foo}';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => 'https://mautic.org',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @testdox Test that a token used in place of a URL is parsed
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedHostWithQuery()
    {
        $url   = 'http://{contactfield=foo}.com?foo=bar';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedHostWithTokenizedQuery()
    {
        $url   = 'http://{contactfield=foo}.com?foo={contactfield=bar}';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => '',
                '{contactfield=bar}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @testdox Test that tokens that are supposed to be ignored are
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testIgnoredTokensAreNotConverted()
    {
        $url   = 'https://{unsubscribe_url}';
        $model = $this->getModel(['{unsubscribe_url}']);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
        $this->assertFalse(strpos($url, $content), 'https:// should have been stripped from the token URL');
    }

    /**
     * @testdox Test that tokens that are supposed to be ignored are
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testUnsupportedTokensAreNotConverted()
    {
        $url   = '{random_token}';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'text'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
    }

    /**
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenWithDefaultValueInPlaintextWillCountAsOne()
    {
        $url          = '{contactfield=website|https://mautic.org}';
        $model        = $this->getModel();
        $inputContent = $this->generateContent($url, 'text');

        list($content, $trackables) = $model->parseContentForTrackables(
            $inputContent,
            [
                '{contactfield=website}' => 'https://mautic.org/about-us',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $trackableKey = '{trackable='.$match[1].'}';
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);

        $this->assertEquals(1, count($trackables));
        $this->assertEquals('{contactfield=website|https://mautic.org}', $trackables[$trackableKey]->getRedirect()->getUrl());
    }

    /**
     * @testdox Test that a URL injected into the do not track list is not converted
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testIgnoredUrlDoesNotCrash()
    {
        $url   = 'https://domain.com';
        $model = $this->getModel([$url]);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $this->assertTrue((strpos($content, $url) !== false), $content);
    }

    /**
     * @testdox Test that a token used in place of a URL is not parsed
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testTokenAsHostIsConvertedToTrackableToken()
    {
        $url   = 'http://{pagelink=1}';
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{pagelink=1}' => 'http://foo-bar.com',
            ],
            'email',
            1
        );

        reset($trackables);
        $token = key($trackables);
        $this->assertNotEmpty($trackables, $content);
        $this->assertContains($token, $content);
    }

    /**
     * @testdox Test that a URLs with same base or correctly replaced
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testUrlsWithSameBaseAreReplacedCorrectly()
    {
        $urls = [
            'https://foo-bar.com',
            'https://foo-bar.com?foo=bar',
        ];

        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($urls, 'html'),
            [],
            'email',
            1
        );

        foreach ($trackables as $redirectId => $trackable) {
            // If the shared base was correctly parsed, all generated tokens will be in the content
            $this->assertNotFalse(strpos($content, $redirectId), $content);
        }
    }

    /**
     * @testdox Test that css images are not converted if there are no links
     */
    public function testCssUrlsAreNotConvertedIfThereAreNoLinks()
    {
        $model = $this->getModel();

        list($content, $trackables) = $model->parseContentForTrackables(
            '<style> .mf-modal { background-image: url(\'https://www.mautic.org/wp-content/uploads/2014/08/iTunesArtwork.png\'); } </style>',
            [],
            'email',
            1
        );

        $this->assertEmpty($trackables);
    }

    /**
     * @testdox Tests that URLs in the plaintext does not contaminate HTML
     */
    public function testPlainTextDoesNotContaminateHtml()
    {
        $model = $this->getModel();

        $html = <<<TEXT
Hi {contactfield=firstname},
<br />
Come to our office in {contactfield=city}! 
<br />
John Smith<br />
VP of Sales<br />
https://plaintexttest.io
TEXT;

        $plainText = strip_tags($html);

        $combined                   = [$html, $plainText];
        list($content, $trackables) = $model->parseContentForTrackables(
            $combined,
            [],
            'email',
            1
        );

        $this->assertCount(1, $trackables);

        // No links so no trackables
        $this->assertEquals($html, $content[0]);

        // Has a URL so has one trackable
        reset($trackables);
        $token = key($trackables);

        $this->assertEquals(str_replace('https://plaintexttest.io', $token, $plainText), $content[1]);
    }

    /**
     * @testdox Tests that URL based contact fields are found in plain text
     */
    public function testPlainTextFindsUrlContactFields()
    {
        $model = $this->getModel([], ['website']);

        $html = <<<TEXT
Hi {contactfield=firstname},
<br />
Come to our office in {contactfield=city}! 
<br />
John Smith<br />
VP of Sales<br />
{contactfield=website}
TEXT;

        $plainText = strip_tags($html);

        $combined                   = [$html, $plainText];
        list($content, $trackables) = $model->parseContentForTrackables(
            $combined,
            [],
            'email',
            1
        );

        $this->assertCount(1, $trackables);

        // No links so no trackables
        $this->assertEquals($html, $content[0]);

        // Has a URL so has one trackable
        reset($trackables);
        $token = key($trackables);

        $this->assertEquals(str_replace('{contactfield=website}', $token, $plainText), $content[1]);
    }

    /**
     * @param array $doNotTrack
     * @param array $urlFieldsForPlaintext
     *
     * @return TrackableModel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getModel($doNotTrack = [], $urlFieldsForPlaintext = [])
    {
        // Add default DoNotTrack
        $doNotTrack = array_merge(
            $doNotTrack,
            [
                '{webview_url}',
                '{unsubscribe_url}',
                '{trackable=(.*?)}',
            ]
        );

        $mockRedirectModel       = $this->createMock(RedirectModel::class);
        $mockLeadFieldRepository = $this->createMock(LeadFieldRepository::class);

        $mockModel = $this->getMockBuilder(TrackableModel::class)
            ->setConstructorArgs([$mockRedirectModel, $mockLeadFieldRepository])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'getContactFieldUrlTokens'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn($doNotTrack);

        $mockModel->expects($this->any())
            ->method('getEntitiesFromUrls')
            ->willReturnCallback(
                function ($trackableUrls, $channel, $channelId) {
                    $entities = [];
                    foreach ($trackableUrls as $url) {
                        $entities[$url] = $this->getTrackableEntity($url);
                    }

                    return $entities;
                }
            );

        $mockModel->expects($this->any())
            ->method('getContactFieldUrlTokens')
            ->willReturn($urlFieldsForPlaintext);

        return $mockModel;
    }

    /**
     * @param $url
     *
     * @return Trackable
     */
    protected function getTrackableEntity($url)
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $trackable = new Trackable();
        $trackable->setChannel('email')
            ->setChannelId(1)
            ->setRedirect($redirect)
            ->setHits(rand(1, 10))
            ->setUniqueHits(rand(1, 10));

        return $trackable;
    }

    /**
     * @param      $urls
     * @param      $type
     * @param bool $doNotTrack
     *
     * @return string
     */
    protected function generateContent($urls, $type, $doNotTrack = false)
    {
        $content = '';
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        foreach ($urls as $url) {
            if ($type == 'html') {
                $dnc = ($doNotTrack) ? ' mautic:disable-tracking' : '';

                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 <a href="$url"$dnc>$url</a> 321ABC
CONTENT;
            } else {
                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 $url 321ABC
CONTENT;
            }
        }

        return $content;
    }
}
