<?php

/* knp_menu.html.twig */
class __TwigTemplate_2636fa24775712ab8dc186056be83a2f59250d4cee3ca8cdc480af0ed7b1781b extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("knp_menu_base.html.twig", "knp_menu.html.twig", 1);
        $this->blocks = array(
            'compressed_root' => array($this, 'block_compressed_root'),
            'root' => array($this, 'block_root'),
            'list' => array($this, 'block_list'),
            'children' => array($this, 'block_children'),
            'item' => array($this, 'block_item'),
            'linkElement' => array($this, 'block_linkElement'),
            'spanElement' => array($this, 'block_spanElement'),
            'label' => array($this, 'block_label'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "knp_menu_base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 11
    public function block_compressed_root($context, array $blocks = array())
    {
        // line 12
        ob_start();
        // line 13
        $this->displayBlock("root", $context, $blocks);
        echo "
";
        echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));
    }

    // line 17
    public function block_root($context, array $blocks = array())
    {
        // line 18
        $context["listAttributes"] = $this->getAttribute(($context["item"] ?? null), "childrenAttributes", array());
        // line 19
        $this->displayBlock("list", $context, $blocks);
    }

    // line 22
    public function block_list($context, array $blocks = array())
    {
        // line 23
        if ((($this->getAttribute(($context["item"] ?? null), "hasChildren", array()) &&  !($this->getAttribute(($context["options"] ?? null), "depth", array()) === 0)) && $this->getAttribute(($context["item"] ?? null), "displayChildren", array()))) {
            // line 24
            echo "    ";
            $context["knp_menu"] = $this;
            // line 25
            echo "    <ul";
            echo $context["knp_menu"]->getattributes(($context["listAttributes"] ?? null));
            echo ">
        ";
            // line 26
            $this->displayBlock("children", $context, $blocks);
            echo "
    </ul>
";
        }
    }

    // line 31
    public function block_children($context, array $blocks = array())
    {
        // line 33
        $context["currentOptions"] = ($context["options"] ?? null);
        // line 34
        $context["currentItem"] = ($context["item"] ?? null);
        // line 36
        if ( !(null === $this->getAttribute(($context["options"] ?? null), "depth", array()))) {
            // line 37
            $context["options"] = twig_array_merge(($context["options"] ?? null), array("depth" => ($this->getAttribute(($context["currentOptions"] ?? null), "depth", array()) - 1)));
        }
        // line 40
        if (( !(null === $this->getAttribute(($context["options"] ?? null), "matchingDepth", array())) && ($this->getAttribute(($context["options"] ?? null), "matchingDepth", array()) > 0))) {
            // line 41
            $context["options"] = twig_array_merge(($context["options"] ?? null), array("matchingDepth" => ($this->getAttribute(($context["currentOptions"] ?? null), "matchingDepth", array()) - 1)));
        }
        // line 43
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["currentItem"] ?? null), "children", array()));
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
            // line 44
            echo "    ";
            $this->displayBlock("item", $context, $blocks);
            echo "
";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 47
        $context["item"] = ($context["currentItem"] ?? null);
        // line 48
        $context["options"] = ($context["currentOptions"] ?? null);
    }

    // line 51
    public function block_item($context, array $blocks = array())
    {
        // line 52
        if ($this->getAttribute(($context["item"] ?? null), "displayed", array())) {
            // line 54
            $context["classes"] = (( !twig_test_empty($this->getAttribute(($context["item"] ?? null), "attribute", array(0 => "class"), "method"))) ? (array(0 => $this->getAttribute(($context["item"] ?? null), "attribute", array(0 => "class"), "method"))) : (array()));
            // line 55
            if ($this->getAttribute(($context["matcher"] ?? null), "isCurrent", array(0 => ($context["item"] ?? null)), "method")) {
                // line 56
                $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "currentClass", array())));
            } elseif ($this->getAttribute(            // line 57
($context["matcher"] ?? null), "isAncestor", array(0 => ($context["item"] ?? null), 1 => $this->getAttribute(($context["options"] ?? null), "matchingDepth", array())), "method")) {
                // line 58
                $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "ancestorClass", array())));
            }
            // line 60
            if ($this->getAttribute(($context["item"] ?? null), "actsLikeFirst", array())) {
                // line 61
                $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "firstClass", array())));
            }
            // line 63
            if ($this->getAttribute(($context["item"] ?? null), "actsLikeLast", array())) {
                // line 64
                $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "lastClass", array())));
            }
            // line 66
            echo "
    ";
            // line 68
            echo "    ";
            if (($this->getAttribute(($context["item"] ?? null), "hasChildren", array()) &&  !($this->getAttribute(($context["options"] ?? null), "depth", array()) === 0))) {
                // line 69
                echo "        ";
                if (( !twig_test_empty($this->getAttribute(($context["options"] ?? null), "branch_class", array())) && $this->getAttribute(($context["item"] ?? null), "displayChildren", array()))) {
                    // line 70
                    $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "branch_class", array())));
                    // line 71
                    echo "        ";
                }
                // line 72
                echo "    ";
            } elseif ( !twig_test_empty($this->getAttribute(($context["options"] ?? null), "leaf_class", array()))) {
                // line 73
                $context["classes"] = twig_array_merge(($context["classes"] ?? null), array(0 => $this->getAttribute(($context["options"] ?? null), "leaf_class", array())));
            }
            // line 76
            $context["attributes"] = $this->getAttribute(($context["item"] ?? null), "attributes", array());
            // line 77
            if ( !twig_test_empty(($context["classes"] ?? null))) {
                // line 78
                $context["attributes"] = twig_array_merge(($context["attributes"] ?? null), array("class" => twig_join_filter(($context["classes"] ?? null), " ")));
            }
            // line 81
            echo "    ";
            $context["knp_menu"] = $this;
            // line 82
            echo "    <li";
            echo $context["knp_menu"]->getattributes(($context["attributes"] ?? null));
            echo ">";
            // line 83
            if (( !twig_test_empty($this->getAttribute(($context["item"] ?? null), "uri", array())) && ( !$this->getAttribute(($context["matcher"] ?? null), "isCurrent", array(0 => ($context["item"] ?? null)), "method") || $this->getAttribute(($context["options"] ?? null), "currentAsLink", array())))) {
                // line 84
                echo "        ";
                $this->displayBlock("linkElement", $context, $blocks);
            } else {
                // line 86
                echo "        ";
                $this->displayBlock("spanElement", $context, $blocks);
            }
            // line 89
            $context["childrenClasses"] = (( !twig_test_empty($this->getAttribute(($context["item"] ?? null), "childrenAttribute", array(0 => "class"), "method"))) ? (array(0 => $this->getAttribute(($context["item"] ?? null), "childrenAttribute", array(0 => "class"), "method"))) : (array()));
            // line 90
            $context["childrenClasses"] = twig_array_merge(($context["childrenClasses"] ?? null), array(0 => ("menu_level_" . $this->getAttribute(($context["item"] ?? null), "level", array()))));
            // line 91
            $context["listAttributes"] = twig_array_merge($this->getAttribute(($context["item"] ?? null), "childrenAttributes", array()), array("class" => twig_join_filter(($context["childrenClasses"] ?? null), " ")));
            // line 92
            echo "        ";
            $this->displayBlock("list", $context, $blocks);
            echo "
    </li>
";
        }
    }

    // line 97
    public function block_linkElement($context, array $blocks = array())
    {
        $context["knp_menu"] = $this;
        echo "<a href=\"";
        echo twig_escape_filter($this->env, $this->getAttribute(($context["item"] ?? null), "uri", array()), "html", null, true);
        echo "\"";
        echo $context["knp_menu"]->getattributes($this->getAttribute(($context["item"] ?? null), "linkAttributes", array()));
        echo ">";
        $this->displayBlock("label", $context, $blocks);
        echo "</a>";
    }

    // line 99
    public function block_spanElement($context, array $blocks = array())
    {
        $context["knp_menu"] = $this;
        echo "<span";
        echo $context["knp_menu"]->getattributes($this->getAttribute(($context["item"] ?? null), "labelAttributes", array()));
        echo ">";
        $this->displayBlock("label", $context, $blocks);
        echo "</span>";
    }

    // line 101
    public function block_label($context, array $blocks = array())
    {
        if (($this->getAttribute(($context["options"] ?? null), "allow_safe_labels", array()) && $this->getAttribute(($context["item"] ?? null), "getExtra", array(0 => "safe_label", 1 => false), "method"))) {
            echo $this->getAttribute(($context["item"] ?? null), "label", array());
        } else {
            echo twig_escape_filter($this->env, $this->getAttribute(($context["item"] ?? null), "label", array()), "html", null, true);
        }
    }

    // line 3
    public function getattributes($__attributes__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals(array(
            "attributes" => $__attributes__,
            "varargs" => $__varargs__,
        ));

        $blocks = array();

        ob_start();
        try {
            // line 4
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["attributes"] ?? null));
            foreach ($context['_seq'] as $context["name"] => $context["value"]) {
                // line 5
                if (( !(null === $context["value"]) &&  !($context["value"] === false))) {
                    // line 6
                    echo sprintf(" %s=\"%s\"", $context["name"], ((($context["value"] === true)) ? (twig_escape_filter($this->env, $context["name"])) : (twig_escape_filter($this->env, $context["value"]))));
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['name'], $context['value'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return ('' === $tmp = ob_get_clean()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
    }

    public function getTemplateName()
    {
        return "knp_menu.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  271 => 6,  269 => 5,  265 => 4,  253 => 3,  243 => 101,  232 => 99,  219 => 97,  210 => 92,  208 => 91,  206 => 90,  204 => 89,  200 => 86,  196 => 84,  194 => 83,  190 => 82,  187 => 81,  184 => 78,  182 => 77,  180 => 76,  177 => 73,  174 => 72,  171 => 71,  169 => 70,  166 => 69,  163 => 68,  160 => 66,  157 => 64,  155 => 63,  152 => 61,  150 => 60,  147 => 58,  145 => 57,  143 => 56,  141 => 55,  139 => 54,  137 => 52,  134 => 51,  130 => 48,  128 => 47,  111 => 44,  94 => 43,  91 => 41,  89 => 40,  86 => 37,  84 => 36,  82 => 34,  80 => 33,  77 => 31,  69 => 26,  64 => 25,  61 => 24,  59 => 23,  56 => 22,  52 => 19,  50 => 18,  47 => 17,  40 => 13,  38 => 12,  35 => 11,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "knp_menu.html.twig", "/var/www/symfony/vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views/knp_menu.html.twig");
    }
}
