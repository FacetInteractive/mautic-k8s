<?php

/* BazingaOAuthServerBundle::error.html.twig */
class __TwigTemplate_ea92c061aaea6bfd0b78cfcd26798051b40c8daae25d8fcf2c231e13ad896dc1 extends Twig_Template
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
        echo "<div>
    <h2>Ooops!</h2>
    <p>It appears you didn't allow <strong>";
        // line 3
        echo twig_escape_filter($this->env, $this->getAttribute(($context["consumer"] ?? null), "name", array()), "html", null, true);
        echo "</strong> to access your private information.</p>
</div>
";
    }

    public function getTemplateName()
    {
        return "BazingaOAuthServerBundle::error.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  23 => 3,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "BazingaOAuthServerBundle::error.html.twig", "/var/www/symfony/vendor/willdurand/oauth-server-bundle/Resources/views/error.html.twig");
    }
}
