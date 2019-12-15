<?php

/* knp_menu_ordered.html.twig */
class __TwigTemplate_bf011ddfe20984eb8d815902819c4265d23fbce9420f594f72b7867e1ea34b48 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("knp_menu.html.twig", "knp_menu_ordered.html.twig", 1);
        $this->blocks = array(
            'list' => array($this, 'block_list'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "knp_menu.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_list($context, array $blocks = array())
    {
        // line 4
        $context["macros"] = $this->loadTemplate("knp_menu.html.twig", "knp_menu_ordered.html.twig", 4);
        // line 5
        echo "
";
        // line 6
        if ((($this->getAttribute(($context["item"] ?? null), "hasChildren", array()) &&  !($this->getAttribute(($context["options"] ?? null), "depth", array()) === 0)) && $this->getAttribute(($context["item"] ?? null), "displayChildren", array()))) {
            // line 7
            echo "    <ol";
            echo $context["macros"]->getattributes(($context["listAttributes"] ?? null));
            echo ">
        ";
            // line 8
            $this->displayBlock("children", $context, $blocks);
            echo "
    </ol>
";
        }
    }

    public function getTemplateName()
    {
        return "knp_menu_ordered.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  43 => 8,  38 => 7,  36 => 6,  33 => 5,  31 => 4,  28 => 3,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "knp_menu_ordered.html.twig", "/var/www/symfony/vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views/knp_menu_ordered.html.twig");
    }
}
