<?php

/* form_table_layout.html.twig */
class __TwigTemplate_e9d796cc60529c6bd565e305e3a43ec3fd8b6a8e5b7ab960b53a64694246f327 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $_trait_0 = $this->loadTemplate("form_div_layout.html.twig", "form_table_layout.html.twig", 1);
        // line 1
        if (!$_trait_0->isTraitable()) {
            throw new Twig_Error_Runtime('Template "'."form_div_layout.html.twig".'" cannot be used as a trait.');
        }
        $_trait_0_blocks = $_trait_0->getBlocks();

        $this->traits = $_trait_0_blocks;

        $this->blocks = array_merge(
            $this->traits,
            array(
                'form_row' => array($this, 'block_form_row'),
                'button_row' => array($this, 'block_button_row'),
                'hidden_row' => array($this, 'block_hidden_row'),
                'form_widget_compound' => array($this, 'block_form_widget_compound'),
            )
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 3
        $this->displayBlock('form_row', $context, $blocks);
        // line 15
        $this->displayBlock('button_row', $context, $blocks);
        // line 24
        $this->displayBlock('hidden_row', $context, $blocks);
        // line 32
        $this->displayBlock('form_widget_compound', $context, $blocks);
    }

    // line 3
    public function block_form_row($context, array $blocks = array())
    {
        // line 4
        echo "<tr>
        <td>";
        // line 6
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'label');
        // line 7
        echo "</td>
        <td>";
        // line 9
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'errors');
        // line 10
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'widget');
        // line 11
        echo "</td>
    </tr>";
    }

    // line 15
    public function block_button_row($context, array $blocks = array())
    {
        // line 16
        echo "<tr>
        <td></td>
        <td>";
        // line 19
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'widget');
        // line 20
        echo "</td>
    </tr>";
    }

    // line 24
    public function block_hidden_row($context, array $blocks = array())
    {
        // line 25
        echo "<tr style=\"display: none\">
        <td colspan=\"2\">";
        // line 27
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'widget');
        // line 28
        echo "</td>
    </tr>";
    }

    // line 32
    public function block_form_widget_compound($context, array $blocks = array())
    {
        // line 33
        echo "<table ";
        $this->displayBlock("widget_container_attributes", $context, $blocks);
        echo ">";
        // line 34
        if ((twig_test_empty($this->getAttribute(($context["form"] ?? null), "parent", array())) && (twig_length_filter($this->env, ($context["errors"] ?? null)) > 0))) {
            // line 35
            echo "<tr>
            <td colspan=\"2\">";
            // line 37
            echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'errors');
            // line 38
            echo "</td>
        </tr>";
        }
        // line 41
        $this->displayBlock("form_rows", $context, $blocks);
        // line 42
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock(($context["form"] ?? null), 'rest');
        // line 43
        echo "</table>";
    }

    public function getTemplateName()
    {
        return "form_table_layout.html.twig";
    }

    public function getDebugInfo()
    {
        return array (  114 => 43,  112 => 42,  110 => 41,  106 => 38,  104 => 37,  101 => 35,  99 => 34,  95 => 33,  92 => 32,  87 => 28,  85 => 27,  82 => 25,  79 => 24,  74 => 20,  72 => 19,  68 => 16,  65 => 15,  60 => 11,  58 => 10,  56 => 9,  53 => 7,  51 => 6,  48 => 4,  45 => 3,  41 => 32,  39 => 24,  37 => 15,  35 => 3,  14 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "form_table_layout.html.twig", "/var/www/symfony/vendor/symfony/twig-bridge/Resources/views/Form/form_table_layout.html.twig");
    }
}
