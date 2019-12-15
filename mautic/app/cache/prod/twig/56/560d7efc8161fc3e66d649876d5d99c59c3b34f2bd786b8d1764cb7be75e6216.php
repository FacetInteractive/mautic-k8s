<?php

/* NoxlogicRateLimitBundle:Default:index.html.twig */
class __TwigTemplate_222b6e1770781e504ac7e326812537d872f5dc14387f3584ab63a1f54c0b26d7 extends Twig_Template
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
        echo "Hello ";
        echo twig_escape_filter($this->env, ($context["name"] ?? null), "html", null, true);
        echo "!
";
    }

    public function getTemplateName()
    {
        return "NoxlogicRateLimitBundle:Default:index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "NoxlogicRateLimitBundle:Default:index.html.twig", "/var/www/symfony/vendor/noxlogic/ratelimit-bundle/Noxlogic/RateLimitBundle/Resources/views/Default/index.html.twig");
    }
}
