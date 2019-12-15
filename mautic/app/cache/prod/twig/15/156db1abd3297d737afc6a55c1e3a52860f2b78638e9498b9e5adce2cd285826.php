<?php

/* LightSamlSpBundle::sessions.html.twig */
class __TwigTemplate_fce6117dc7e8864f0f94e7204671119d62e830c0b30267affc50e9150cad5652 extends Twig_Template
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
        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
        \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
<head>
    <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />
</head>
<body>

<h1>SAML Sessions</h1>
";
        // line 10
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["sessions"] ?? null));
        $context['_iterated'] = false;
        foreach ($context['_seq'] as $context["_key"] => $context["session"]) {
            // line 11
            echo "    <ul data-session>
        <li data-idp=\"";
            // line 12
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "idpEntityId", array()), "html", null, true);
            echo "\">IDP: ";
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "idpEntityId", array()), "html", null, true);
            echo "</li>
        <li data-sp=\"";
            // line 13
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "spEntityId", array()), "html", null, true);
            echo "\">SP: ";
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "spEntityId", array()), "html", null, true);
            echo "</li>
        <li>NameID: ";
            // line 14
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "nameId", array()), "html", null, true);
            echo "</li>
        <li>NameIDFormat: ";
            // line 15
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "nameIdFormat", array()), "html", null, true);
            echo "</li>
        <li>SessionIndex: ";
            // line 16
            echo twig_escape_filter($this->env, $this->getAttribute($context["session"], "sessionIndex", array()), "html", null, true);
            echo "</li>
        <li>AuthnInstant: ";
            // line 17
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($context["session"], "sessionInstant", array()), "Y-m-d H:i:s P"), "html", null, true);
            echo "</li>
        <li>FirstAuthOn: ";
            // line 18
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($context["session"], "firstAuthOn", array()), "Y-m-d H:i:s P"), "html", null, true);
            echo "</li>
        <li>LastAuthOn: ";
            // line 19
            echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute($context["session"], "lastAuthOn", array()), "Y-m-d H:i:s P"), "html", null, true);
            echo "</li>
    </ul>
";
            $context['_iterated'] = true;
        }
        if (!$context['_iterated']) {
            // line 22
            echo "    <p>There are no SAML sessions established</p>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['session'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 24
        echo "</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "LightSamlSpBundle::sessions.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  85 => 24,  78 => 22,  70 => 19,  66 => 18,  62 => 17,  58 => 16,  54 => 15,  50 => 14,  44 => 13,  38 => 12,  35 => 11,  30 => 10,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "LightSamlSpBundle::sessions.html.twig", "/var/www/symfony/vendor/lightsaml/sp-bundle/src/LightSaml/SpBundle/Resources/views/sessions.html.twig");
    }
}
