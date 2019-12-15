<?php

/* TwigBundle:Exception:trace.html.twig */
class __TwigTemplate_5df931e16782513eac2c9a768273949b4cf3285a83bb28b6187785db745a3b30 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        if ($this->getAttribute(($context["trace"] ?? null), "function", array())) {
            // line 2
            echo "    at
    <strong>
        <abbr title=\"";
            // line 4
            echo twig_escape_filter($this->env, $this->getAttribute(($context["trace"] ?? null), "class", array()), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute(($context["trace"] ?? null), "short_class", array()), "html", null, true);
            echo "</abbr>
        ";
            // line 5
            echo twig_escape_filter($this->env, ($this->getAttribute(($context["trace"] ?? null), "type", array()) . $this->getAttribute(($context["trace"] ?? null), "function", array())), "html", null, true);
            echo "
    </strong>
    (";
            // line 7
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\CodeExtension')->formatArgs($this->getAttribute(($context["trace"] ?? null), "args", array()));
            echo ")
";
        }
        // line 9
        echo "
";
        // line 10
        if (((($this->getAttribute(($context["trace"] ?? null), "file", array(), "any", true, true) && $this->getAttribute(($context["trace"] ?? null), "file", array())) && $this->getAttribute(($context["trace"] ?? null), "line", array(), "any", true, true)) && $this->getAttribute(($context["trace"] ?? null), "line", array()))) {
            // line 11
            echo "    ";
            echo (($this->getAttribute(($context["trace"] ?? null), "function", array())) ? ("<br />") : (""));
            echo "
    in ";
            // line 12
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\CodeExtension')->formatFile($this->getAttribute(($context["trace"] ?? null), "file", array()), $this->getAttribute(($context["trace"] ?? null), "line", array()));
            echo "&nbsp;
    ";
            // line 13
            ob_start();
            // line 14
            echo "    <a href=\"#\" onclick=\"toggle('trace-";
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "'); switchIcons('icon-";
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "-open', 'icon-";
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "-close'); return false;\">
        <img class=\"toggle\" id=\"icon-";
            // line 15
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "-close\" alt=\"-\" src=\"data:image/gif;base64,R0lGODlhEgASAMQSANft94TG57Hb8GS44ez1+mC24IvK6ePx+Wa44dXs92+942e54o3L6W2844/M6dnu+P/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABIALAAAAAASABIAQAVCoCQBTBOd6Kk4gJhGBCTPxysJb44K0qD/ER/wlxjmisZkMqBEBW5NHrMZmVKvv9hMVsO+hE0EoNAstEYGxG9heIhCADs=\" style=\"display: ";
            echo (((0 == ($context["i"] ?? null))) ? ("inline") : ("none"));
            echo "\" />
        <img class=\"toggle\" id=\"icon-";
            // line 16
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "-open\" alt=\"+\" src=\"data:image/gif;base64,R0lGODlhEgASAMQTANft99/v+Ga44bHb8ITG52S44dXs9+z1+uPx+YvK6WC24G+944/M6W28443L6dnu+Ge54v/+/l614P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAABMALAAAAAASABIAQAVS4DQBTiOd6LkwgJgeUSzHSDoNaZ4PU6FLgYBA5/vFID/DbylRGiNIZu74I0h1hNsVxbNuUV4d9SsZM2EzWe1qThVzwWFOAFCQFa1RQq6DJB4iIQA7\" style=\"display: ";
            echo (((0 == ($context["i"] ?? null))) ? ("none") : ("inline"));
            echo "\" />
    </a>
    ";
            echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));
            // line 19
            echo "    <div id=\"trace-";
            echo twig_escape_filter($this->env, ((($context["prefix"] ?? null) . "-") . ($context["i"] ?? null)), "html", null, true);
            echo "\" style=\"display: ";
            echo (((0 == ($context["i"] ?? null))) ? ("block") : ("none"));
            echo "\" class=\"trace\">
        ";
            // line 20
            echo $this->env->getExtension('Symfony\Bridge\Twig\Extension\CodeExtension')->fileExcerpt($this->getAttribute(($context["trace"] ?? null), "file", array()), $this->getAttribute(($context["trace"] ?? null), "line", array()));
            echo "
    </div>
";
        }
    }

    public function getTemplateName()
    {
        return "TwigBundle:Exception:trace.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  87 => 20,  80 => 19,  72 => 16,  66 => 15,  57 => 14,  55 => 13,  51 => 12,  46 => 11,  44 => 10,  41 => 9,  36 => 7,  31 => 5,  25 => 4,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "TwigBundle:Exception:trace.html.twig", "/var/www/symfony/vendor/symfony/twig-bundle/Resources/views/Exception/trace.html.twig");
    }
}
