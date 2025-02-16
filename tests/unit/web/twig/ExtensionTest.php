<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web\twig;

use ArrayObject;
use Codeception\Test\Unit;
use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\fields\MissingField;
use craft\fields\PlainText;
use craft\test\TestSetup;
use craft\web\View;
use crafttests\fixtures\GlobalSetFixture;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 * Unit tests for the Various functions in the Extension class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ExtensionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var View
     */
    protected $view;

    public function _fixtures(): array
    {
        return [
            'globals' => [
                'class' => GlobalSetFixture::class,
            ],
        ];
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGlobals()
    {
        // We want web for this part.
        Craft::$app->getRequest()->setIsConsoleRequest(false);
        $user = new User(['firstName' => 'John', 'lastName' => 'Smith']);
        Craft::$app->getUser()->setIdentity($user);
        Craft::$app->getRequest()->setRawBody('This is a raw body');

        // Current user
        $this->testRenderResult(
            'John | Smith',
            '{{ currentUser.firstName }} | {{ currentUser.lastName }}'
        );

        // Craft variable - poke various calls.
        $this->testRenderResult(
            'John',
            '{{ craft.app.user.getIdentity().firstName }}'
        );

        $this->testRenderResult(
            'This is a raw body',
            '{{ craft.app.request.getRawBody() }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function testCraftSystemGlobals()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $this->testRenderResult(
            '' . Craft::$app->getEdition() . ' | ' . Craft::Solo . ' | ' . Craft::Pro,
            Craft::$app->getEdition() . ' | 0 | 1'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGlobalsWithUninstalledCraft()
    {
        Craft::$app->setIsInstalled(false);
        $this->testRenderResult(
            ' |  |  | ',
            '{{ systemName }} | {{ currentSite }} | {{ siteName }} | {{ siteUrl }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function testSiteGlobals()
    {
        Craft::$app->getProjectConfig()->set('system.name', 'Im a test system');
        $this->testRenderResult(
            'Im a test system | default Craft test site ' . TestSetup::SITE_URL,
            '{{ systemName }} | {{ currentSite.handle }} {{ currentSite }} {{ siteUrl }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function testElementGlobals()
    {
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $this->testRenderResult(
            'A global set | A different global set',
            '{{ aGlobalSet }} | {{ aDifferentGlobalSet }}'
        );
    }

    /**
     *
     */
    public function testInstanceOfTest()
    {
        $this->testRenderResult(
            'yes',
            '{{ foo is instance of(class) ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
                'class' => PlainText::class,
            ]
        );
        $this->testRenderResult(
            'no',
            '{{ foo is instance of(class) ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
                'class' => 'foo\\bar\\Baz',
            ]
        );
    }

    /**
     *
     */
    public function testMissingTest()
    {
        $this->testRenderResult(
            'yes',
            '{{ foo is missing ? "yes" : "no" }}',
            [
                'foo' => new MissingField(),
            ]
        );
        $this->testRenderResult(
            'no',
            '{{ foo is missing ? "yes" : "no" }}',
            [
                'foo' => new PlainText(),
            ]
        );
    }

    /**
     *
     */
    public function testTranslateFilter()
    {
        $this->testRenderResult(
            'Translated message',
            '{{ "Source message"|t("site") }}'
        );
        $this->testRenderResult(
            'Translated message with foo',
            '{{ "Source message with {var}"|t("site", {var: myVar}) }}',
            [
                'myVar' => 'foo',
            ]
        );

        // 'site' category is optional
        $this->testRenderResult(
            'Translated message',
            '{{ "Source message"|t }}'
        );
        $this->testRenderResult(
            'Translated message with foo',
            '{{ "Source message with {var}"|t({var: myVar}) }}',
            [
                'myVar' => 'foo',
            ]
        );

        // |translate should swallow the InvalidConfigException here
        $this->testRenderResult(
            'Source message',
            '{{ "Source message"|t("invalidCategory") }}'
        );

        $this->expectException(InvalidConfigException::class);
        Craft::t('invalidCategory', 'Source message');
        $this->view->renderString('{{ "Source message"|t("invalidCategory") }}');
    }

    /**
     *
     */
    public function testTruncateFilter()
    {
        $this->testRenderResult(
            '',
            '{{ ""|truncate(8) }}'
        );
        $this->testRenderResult(
            'Test...',
            '{{ "Test foo bar"|truncate(8, "...") }}'
        );
    }

    /**
     *
     */
    public function testUcfirstFilter()
    {
        $this->testRenderResult(
            'Foo bar',
            '{{ "foo bar"|ucfirst }}'
        );
    }

    /**
     * @deprecated
     */
    public function testUcwordsFilter()
    {
        $this->testRenderResult(
            'Foo Bar',
            '{{ "foo bar"|ucwords }}'
        );
    }

    /**
     *
     */
    public function testLcfirstFilter()
    {
        $this->testRenderResult(
            'foo Bar',
            '{{ "Foo Bar"|lcfirst }}'
        );
    }

    /**
     *
     */
    public function testKebabFilter()
    {
        $this->testRenderResult(
            'foo-bar',
            '{{ "foo bar"|kebab }}'
        );
    }

    /**
     *
     */
    public function testCamelFilter()
    {
        $this->testRenderResult(
            'fooBar',
            '{{ "foo bar"|camel }}'
        );
    }

    /**
     *
     */
    public function testPascalFilter()
    {
        $this->testRenderResult(
            'FooBar',
            '{{ "foo bar"|pascal }}'
        );
    }

    /**
     *
     */
    public function testSnakeFilter()
    {
        $this->testRenderResult(
            'foo_bar',
            '{{ "foo bar"|snake }}'
        );
    }

    /**
     *
     */
    public function testJsonEncodeFilter()
    {
        $this->testRenderResult(
            '{"foo":true}',
            '{{ myVar|json_encode }}',
            [
                'myVar' => ['foo' => true],
            ]
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testWithoutFilter()
    {
        $this->testRenderResult(
            'foo,bar',
            '{{ ["foo","bar","baz"]|without("baz")|join(",") }}'
        );
        $this->testRenderResult(
            'foo',
            '{{ ["foo","bar","baz"]|without(["bar","baz"])|join(",") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testWithoutKeyFilter()
    {
        $this->testRenderResult(
            'foo,bar',
            '{{ {a:"foo",b:"bar",c:"baz"}|withoutKey("c")|join(",") }}'
        );
        $this->testRenderResult(
            'foo',
            '{{ {a:"foo",b:"bar",c:"baz"}|withoutKey(["b","c"])|join(",") }}'
        );
    }

    /**
     *
     */
    public function testAttrFilter()
    {
        $this->testRenderResult(
            '<p class="foo">Hey</p>',
            '{{ "<p>Hey</p>"|attr({class: "foo"}) }}'
        );

        // |attr should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'Hey',
            '{{ "Hey"|attr({class: "foo"}) }}'
        );
    }

    /**
     *
     */
    public function testParseAttrFilter()
    {
        $this->testRenderResult(
            '{"id":"foo","class":["bar","baz"]}',
            '{{ \'<p id="foo" class="bar baz">Hello</p>\'|parseAttr|json_encode }}'
        );

        // |parseAttr should swallow the InvalidArgumentException here
        $this->testRenderResult(
            '[]',
            '{{ "foo"|parseAttr|json_encode }}'
        );
    }

    /**
     *
     */
    public function testParseRefsFilter()
    {
        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ "{user:1:username}"|parseRefs }}'
        );
    }

    /**
     *
     */
    public function testAppendFilter()
    {
        $this->testRenderResult(
            '<p><span>foo</span><span>bar</span></p>',
            '{{ "<p><span>foo</span></p>"|append("<span>bar</span>") }}'
        );
        $this->testRenderResult(
            '<p><span>foo</span></p>',
            '{{ "<p><span>bar</span></p>"|append("<span>foo</span>", "replace") }}'
        );
    }

    /**
     *
     */
    public function testPrependFilter()
    {
        $this->testRenderResult(
            '<p><span>foo</span><span>bar</span></p>',
            '{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>") }}'
        );
        $this->testRenderResult(
            '<p><span>foo</span></p>',
            '{{ "<p><span>bar</span></p>"|prepend("<span>foo</span>", "replace") }}'
        );
    }

    /**
     *
     */
    public function testPurifyFilter()
    {
        $this->testRenderResult(
            '<p>foo</p>',
            '{{ \'<p bad-attr="bad-value">foo</p>\'|purify }}'
        );
    }

    /**
     *
     */
    public function testPushFilter()
    {
        $this->testRenderResult(
            '["foo","bar","baz"]',
            '{{ ["foo"]|push("bar", "baz")|json_encode }}'
        );
    }

    /**
     *
     */
    public function testUnshiftFilter()
    {
        $this->testRenderResult(
            '["foo","bar","baz"]',
            '{{ ["baz"]|unshift("foo", "bar")|json_encode }}'
        );
    }

    /**
     *
     */
    public function testRemoveClassFilter()
    {
        $this->testRenderResult('<div>', '{{ \'<div class="foo">\'|removeClass("foo") }}');
        $this->testRenderResult('<div class="bar">', '{{ \'<div class="foo bar">\'|removeClass("foo") }}');
        $this->testRenderResult('<div class="baz">', '{{ \'<div class="foo bar baz">\'|removeClass(["foo", "bar"]) }}');
        $this->testRenderResult('foo', '{{ \'foo\'|removeClass("foo") }}');
    }

    /**
     *
     */
    public function testReplaceFilter()
    {
        $this->testRenderResult(
            'qux quux corge',
            '{{ "foo bar baz"|replace({foo: "qux", bar: "quux", baz: "corge"}) }}'
        );

        $this->testRenderResult(
            'qux',
            '{{ "foo bar baz"|replace("/f.*z/", "qux") }}'
        );

        $this->testRenderResult(
            'foo qux baz',
            '{{ "foo bar baz"|replace("bar", "qux") }}'
        );
    }

    /**
     *
     */
    public function testDateFilter()
    {
        // DateInterval
        $this->testRenderResult(
            '4 days',
            '{{ d|date("%d days") }}',
            [
                'd' => new \DateInterval('P2Y4DT6H8M'),
            ]
        );

        $d = new \DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("icu:YYYY-MM-dd") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("Y-m-d") }}',
            compact('d')
        );
        $this->testRenderResult(
            '2021-01-20',
            '{{ d|date("php:Y-m-d") }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testTimeFilter()
    {
        $d = new \DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("icu:HH:mm:ss") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("h:i:s") }}',
            compact('d')
        );
        $this->testRenderResult(
            '10:00:00',
            '{{ d|time("php:h:i:s") }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testDatetimeFilter()
    {
        $d = new \DateTime('2021-01-20 10:00:00');

        // ICU format
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("icu:YYYY-MM-dd HH:mm:ss") }}',
            compact('d')
        );

        // PHP format
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("Y-m-d h:i:s") }}',
            compact('d')
        );
        $this->testRenderResult(
            '2021-01-20 10:00:00',
            '{{ d|datetime("php:Y-m-d h:i:s") }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testAtomFilter()
    {
        $d = new \DateTime();
        $this->testRenderResult(
            $d->format(\DateTime::ATOM),
            '{{ d|atom }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testRssFilter()
    {
        $d = new \DateTime();
        $this->testRenderResult(
            $d->format(\DateTime::RSS),
            '{{ d|rss }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testHttpdateFilter()
    {
        $d = new \DateTime();
        $this->testRenderResult(
            $d->format(\DateTime::RFC7231),
            '{{ d|httpdate }}',
            compact('d')
        );
    }

    /**
     *
     */
    public function testEncencFilter()
    {
        $enc = $this->view->renderString('{{ "foo"|encenc }}');
        self::assertStringStartsWith('base64:', $enc);
    }

    /**
     *
     */
    public function testFilterFilter()
    {
        $this->testRenderResult(
            'foo bar baz',
            '{{ ["foo", "", "bar", "", "baz"]|filter|join(" ") }}'
        );

        $this->testRenderResult(
            'foo bar',
            '{{ ["foo", "bar", "baz"]|filter(i => i != "baz")|join(" ") }}'
        );
    }

    /**
     *
     */
    public function testGroupFilter()
    {
        // deprecated element query
        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ craft.users().id(1)|group("username")|keys|join(",") }}'
        );

        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ craft.users().id(1).all()|group("username")|keys|join(",") }}'
        );

        $this->testRenderResult(
            TestSetup::USERNAME,
            '{{ craft.users().id(1).all()|group(u => u.username)|keys|join(",") }}'
        );

        // invalid value
        self::expectException(RuntimeError::class);
        $this->view->renderString('{% do "foo"|group("bar") %}');
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testIndexOfFilter()
    {
        $array = new ArrayObject(['John', 'Smith']);

        $this->testRenderResult(
            '3',
            '{{ "Im a string"|indexOf("a") }}'
        );

        $this->testRenderResult(
            '1',
            '{{ [2, 3, 4, 5]|indexOf(3) }}'
        );

        $this->testRenderResult(
            '1',
            '{{ array|indexOf("Smith") }}',
            compact('array')
        );

        $this->testRenderResult(
            '-1',
            '{{ array|indexOf("Doe") }}',
            compact('array')
        );
    }

    /**
     *
     */
    public function testLiteralFilter()
    {
        $this->testRenderResult(
            '\\*foo\\*',
            '{{ "*foo*"|literal }}'
        );
    }

    /**
     *
     */
    public function testMarkdownFilter()
    {
        $this->testRenderResult(
            "<p><strong>Hello</strong></p>\n",
            '{{ "**Hello**"|md }}'
        );

        $this->testRenderResult(
            "<p><strong>Hello</strong></p>\n",
            '{{ "**Hello**"|markdown }}'
        );

        $this->testRenderResult(
            '<strong>Hello</strong>',
            '{{ "**Hello**"|md(inlineOnly=true) }}'
        );
    }

    /**
     *
     */
    public function testMergeFilter()
    {
        $this->testRenderResult(
            'foo bar baz',
            '{{ ["foo"]|merge(["bar", "baz"])|join(" ") }}'
        );

        $this->testRenderResult(
            '{"f":"foo","b":["baz"]}',
            '{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]})|json_encode }}'
        );

        $this->testRenderResult(
            '{"f":"foo","b":["bar","baz"]}',
            '{{ {f: "foo", b: ["bar"]}|merge({b: ["baz"]}, recursive=true)|json_encode }}'
        );
    }

    /**
     *
     */
    public function testMultisortFilter()
    {
        $this->testRenderResult(
            'bar baz foo',
            '{{ [{k:"foo"},{k:"bar"},{k:"baz"}]|multisort("k")|column("k")|join(" ") }}'
        );
    }

    /**
     *
     */
    public function testCurrencyFilter()
    {
        $this->testRenderResult(
            '',
            '{{ null|currency }}'
        );

        $this->testRenderResult(
            '$299.00',
            '{{ 299|currency }}'
        );

        $this->testRenderResult(
            '$299',
            '{{ 299|currency(stripZeros=true) }}'
        );

        // |currency should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|currency }}'
        );
    }

    /**
     *
     */
    public function testFilesizeFilter()
    {
        $this->testRenderResult(
            '',
            '{{ null|filesize }}'
        );

        $this->testRenderResult(
            '1 KB',
            '{{ 1000|filesize }}'
        );

        // |filesize should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|filesize }}'
        );
    }

    /**
     *
     */
    public function testNumberFilter()
    {
        $this->testRenderResult(
            '',
            '{{ null|number }}'
        );

        $this->testRenderResult(
            '1,000',
            '{{ 1000|number }}'
        );

        $this->testRenderResult(
            '1,000.00',
            '{{ 1000|number(decimals=2) }}'
        );

        // |number should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|number }}'
        );
    }

    /**
     *
     */
    public function testPercentageFilter()
    {
        $this->testRenderResult(
            '',
            '{{ null|percentage }}'
        );

        $this->testRenderResult(
            '80%',
            '{{ 0.8|percentage }}'
        );

        $this->testRenderResult(
            '80.0%',
            '{{ 0.8|percentage(decimals=1) }}'
        );

        // |percentage should swallow the InvalidArgumentException here
        $this->testRenderResult(
            'foo',
            '{{ "foo"|percentage }}'
        );
    }

    /**
     *
     */
    public function testWidontFilter()
    {
        $this->testRenderResult('foo bar&nbsp;baz', '{{ "foo bar baz"|widont }}');
    }

    /**
     *
     */
    public function testCloneFunction()
    {
        $this->testRenderResult(
            'yes',
            '{% set q2 = clone(q) %}{{ q2.sectionId == q.sectionId and q2 is not same as(q) ? "yes" : "no" }}',
            [
                'q' => Entry::find()->sectionId(10),
            ]
        );
    }

    /**
     *
     */
    public function testDataUrlFunction()
    {
        $path = dirname(__DIR__, 3) . '/_data/assets/files/craft-logo.svg';
        $dataUrl = $this->view->renderString('{{ dataUrl(path) }}', compact('path'));
        self::assertStringStartsWith('data:image/svg+xml;base64,', $dataUrl);
    }

    public function testExpressionFunction()
    {
        $this->testRenderResult(
            'Im an expression | var | Im an expression',
            '{% set expression =  expression("Im an expression", ["var"]) %}{{ expression }} | {{ expression.params[0] }} | {{ expression.expression }}'
        );
    }

    /**
     *
     */
    public function testGqlFunction()
    {
        $this->testRenderResult(
            '{"data":{"ping":"pong"}}',
            '{{ gql("{ping}")|json_encode }}'
        );
    }

    /**
     *
     */
    public function testPluginFunction()
    {
        $this->testRenderResult(
            'invalid',
            '{{ plugin("no-a-real-plugin") is same as(null) ? "invalid" }}'
        );
    }

    /**
     * @deprecated
     */
    public function testRoundFunction()
    {
        $this->testRenderResult(
            '5',
            '{{ round(4.8) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testShuffleFunction()
    {
        $array = [
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        ];

        $this->testRenderResult(
            'yes',
            '{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}',
            compact('array')
        );

        $this->testRenderResult(
            'yes',
            '{{ array != shuffle(array) or array != shuffle(array) ? "yes" : "no" }}',
            [
                'array' => new ArrayObject($array),
            ]
        );
    }

    /**
     *
     */
    public function testSvgFunction()
    {
        $path = dirname(__DIR__, 3) . '/_data/assets/files/craft-logo.svg';
        $contents = file_get_contents($path);

        $svg = $this->view->renderString('{{ svg(path) }}', compact('path'));
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('id="Symbols"', $svg);

        $svg = $this->view->renderString('{{ svg(contents) }}', compact('contents'));
        self::assertStringStartsWith("<svg", $svg);
        self::assertRegExp('/id="\w+\-Symbols"/', $svg);

        $svg = $this->view->renderString('{{ svg(contents, namespace=false) }}', compact('contents'));
        self::assertStringStartsWith("<svg", $svg);
        self::assertStringContainsString('id="Symbols"', $svg);

        // deprecated
        $svg = $this->view->renderString('{{ svg(contents, class="foobar") }}', compact('contents'));
        self::assertStringContainsString('class="foobar"', $svg);
    }

    /**
     *
     */
    public function testTagFunction()
    {
        $this->testRenderResult(
            '<p class="foo">Hello</p>',
            '{{ tag("p", {text: "Hello", class: "foo"}) }}'
        );

        $this->testRenderResult(
            '<p>&lt;script&gt;alert(&#039;Hello&#039;);&lt;/script&gt;</p>',
            '{{ tag("p", {text: "<script>alert(\'Hello\');</script>"}) }}'
        );

        $this->testRenderResult(
            '<p><script>alert(\'Hello\');</script></p>',
            '{{ tag("p", {html: "<script>alert(\'Hello\');</script>"}) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testCsrfInputFunction()
    {
        Craft::$app->getConfig()->getGeneral()->enableCsrfProtection = true;
        $this->testRenderResult(
            '<input type="hidden" name="CRAFT_CSRF_TOKEN" value="' . Craft::$app->getRequest()->getCsrfToken() . '">',
            '{{ csrfInput() }}'
        );

        // Custom name - just to be sure.
        Craft::$app->getRequest()->csrfParam = 'HACKER_POOF';
        $this->testRenderResult(
            '<input type="hidden" name="HACKER_POOF" value="' . Craft::$app->getRequest()->getCsrfToken() . '">',
            '{{ csrfInput() }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testRedirectInputFunction()
    {
        $this->testRenderResult(
            '<input type="hidden" name="redirect" value="' . Craft::$app->getSecurity()->hashData('A URL') . '">',
            '{{ redirectInput("A URL") }}'
        );

        $this->testRenderResult(
            '<input type="hidden" name="redirect" value="' . Craft::$app->getSecurity()->hashData('A URL WITH CHARS !@#$%^*()😋') . '">',
            '{{ redirectInput("A URL WITH CHARS !@#$%^*()😋") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testActionInputFunction()
    {
        $this->testRenderResult(
            '<input type="hidden" name="action" value="A URL">',
            '{{ actionInput("A URL") }}'
        );

        $this->testRenderResult(
            '<input type="hidden" name="action" value="A URL WITH CHARS !@#$%^&amp;*()😋">',
            '{{ actionInput("A URL WITH CHARS !@#$%^&*()😋") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testRenderObjectTemplateFunction()
    {
        // This is some next level inception stuff IMO.....
        $this->testRenderResult(
            'John',
            '{{ renderObjectTemplate("{{ object.firstName}}", {firstName: "John"}) }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testGetenvFunction()
    {
        $this->testRenderResult(
            'Craft CMS | info@craftcms.com',
            '{{ getenv("FROM_EMAIL_NAME") }} | {{ getenv("FROM_EMAIL_ADDRESS") }}'
        );
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function testParseEnvFunction()
    {
        $this->testRenderResult(
            'Craft CMS',
            '{{ parseEnv("$FROM_EMAIL_NAME") }}'
        );

        $this->testRenderResult(
            'FROM_EMAIL_NAME',
            '{{ parseEnv("FROM_EMAIL_NAME") }}'
        );
    }

    /**
     * @param string $expectedString
     * @param string $renderString
     * @param array $variables
     * @throws LoaderError
     * @throws SyntaxError
     */
    protected function testRenderResult(string $expectedString, string $renderString, array $variables = [])
    {
        $result = $this->view->renderString($renderString, $variables);
        self::assertSame(
            $expectedString,
            $result
        );
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->view = Craft::$app->getView();
    }
}
