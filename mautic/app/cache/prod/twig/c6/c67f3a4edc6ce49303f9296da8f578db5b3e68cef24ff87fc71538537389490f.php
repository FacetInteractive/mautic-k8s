<?php

/* FOSOAuthServerBundle:Authorize:authorize_content.html.twig */
class __TwigTemplate_eefce2f086c6569ed974c2679c6f3bb533c8e02b7ad6f96b522d63be05795b6e extends Twig_Template
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
        echo         $this->env->getExtension('form')->renderer->renderBlock(($context["form"] ?? null), 'form_start', array("method" => "POST", "action" => $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("fos_oauth_server_authorize"), "label_attr" => array("class" => "fos_oauth_server_authorize")));
        echo "
    <input type=\"submit\" name=\"accepted\" value=\"";
        // line 2
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\TranslationExtension')->trans("authorize.accept", array(), "FOSOAuthServerBundle"), "html", null, true);
        echo "\" />
    <input type=\"submit\" name=\"rejected\" value=\"";
        // line 3
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\TranslationExtension')->trans("authorize.reject", array(), "FOSOAuthServerBundle"), "html", null, true);
        echo "\" />

    ";
        // line 5
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "client_id", array()), 'row');
        echo "
    ";
        // line 6
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "response_type", array()), 'row');
        echo "
    ";
        // line 7
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "redirect_uri", array()), 'row');
        echo "
    ";
        // line 8
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "state", array()), 'row');
        echo "
    ";
        // line 9
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock($this->getAttribute(($context["form"] ?? null), "scope", array()), 'row');
        echo "
    ";
        // line 10
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'rest');
        echo "
</form>
";
    }

    public function getTemplateName()
    {
        return "FOSOAuthServerBundle:Authorize:authorize_content.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  52 => 10,  48 => 9,  44 => 8,  40 => 7,  36 => 6,  32 => 5,  27 => 3,  23 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "FOSOAuthServerBundle:Authorize:authorize_content.html.twig", "/var/www/symfony/vendor/friendsofsymfony/oauth-server-bundle/Resources/views/Authorize/authorize_content.html.twig");
    }
}
