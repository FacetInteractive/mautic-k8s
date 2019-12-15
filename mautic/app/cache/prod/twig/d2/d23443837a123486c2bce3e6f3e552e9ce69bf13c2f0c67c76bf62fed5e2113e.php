<?php

/* LightSamlSpBundle::discovery.html.twig */
class __TwigTemplate_93f3d14c53704b2f32e20135a4c283f9ba17ba6a65a0a8a901a4c30732b817f5 extends Twig_Template
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
    <link rel=\"stylesheet\" href=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\AssetExtension')->getAssetUrl("/media/css/libraries.css"), "html", null, true);
        echo "\" data-source=\"mautic\">
    <link rel=\"stylesheet\" href=\"";
        // line 7
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\AssetExtension')->getAssetUrl("/media/css/app.css"), "html", null, true);
        echo "\" data-source=\"mautic\">
</head>
<body>
<div class=\"container\">
    <div class=\"well mt-15\">
        <h4 class=\"text-center\">SAML not configured or configured incorrectly.</h4>
    </div>
</div>
</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "LightSamlSpBundle::discovery.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  30 => 7,  26 => 6,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "LightSamlSpBundle::discovery.html.twig", "/var/www/symfony/app/Resources/LightSamlSpBundle/views/discovery.html.twig");
    }
}
