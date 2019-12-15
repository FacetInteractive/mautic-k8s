<?php

/* LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig */
class __TwigTemplate_68312616c72c6ce6ca2409bb68b75957177bcef815aa7d85a04ef21de6e83049 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("WebProfilerBundle:Profiler:layout.html.twig", "LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig", 1);
        $this->blocks = array(
            'toolbar' => array($this, 'block_toolbar'),
            'menu' => array($this, 'block_menu'),
            'panel' => array($this, 'block_panel'),
            'table_pheanstalk' => array($this, 'block_table_pheanstalk'),
            'table_tube' => array($this, 'block_table_tube'),
            'table_jobs' => array($this, 'block_table_jobs'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "WebProfilerBundle:Profiler:layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_toolbar($context, array $blocks = array())
    {
        // line 4
        echo "    <div class=\"sf-toolbar-block sf-toolbar-block-leezy-pheanstalk sf-toolbar-status-normal\">
        <a href=\"";
        // line 5
        echo twig_escape_filter($this->env, $this->env->getExtension('Symfony\Bridge\Twig\Extension\RoutingExtension')->getPath("_profiler", array("token" => ($context["token"] ?? null), "panel" => ($context["name"] ?? null))), "html", null, true);
        echo "\">
            <div class=\"sf-toolbar-icon\">
                ";
        // line 7
        echo twig_include($this->env, $context, "@LeezyPheanstalk/Profiler/queue.svg");
        echo "
                <span class=\"sf-toolbar-value\">";
        // line 8
        echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "jobCount", array()), "html", null, true);
        echo "</span>
                <span class=\"sf-toolbar-label\">Jobs</span>
            </div>
        </a>
        <div class=\"sf-toolbar-info\">
            <div class=\"sf-toolbar-status\">
                <b>Pheanstalks</b>
                <span>";
        // line 15
        echo twig_escape_filter($this->env, twig_length_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "pheanstalks", array())), "html", null, true);
        echo "</span>
            </div>
            <div class=\"sf-toolbar-status\">
                <b>Tubes</b>
                <span>";
        // line 19
        echo twig_escape_filter($this->env, twig_length_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "tubes", array())), "html", null, true);
        echo "</span>
            </div>
            <div class=\"sf-toolbar-status\">
                <b>Jobs</b>
                <span>";
        // line 23
        echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "jobCount", array()), "html", null, true);
        echo "</span>
            </div>
        </div>
    </div>
";
    }

    // line 29
    public function block_menu($context, array $blocks = array())
    {
        // line 30
        echo "    <span class=\"label\">
        <span class=\"icon\">";
        // line 31
        echo twig_include($this->env, $context, "@LeezyPheanstalk/Profiler/queue.svg");
        echo "</span>
        <strong>Pheanstalk</strong>
        <span class=\"count\">
            <span>";
        // line 34
        echo twig_escape_filter($this->env, twig_length_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "pheanstalks", array())), "html", null, true);
        echo "</span>
            <span>";
        // line 35
        echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "jobCount", array()), "html", null, true);
        echo " Jb</span>
        </span>
    </span>
";
    }

    // line 40
    public function block_panel($context, array $blocks = array())
    {
        // line 41
        echo "    <h2>Pheanstalks</h2>
    ";
        // line 42
        $context["data"] = $this->getAttribute(($context["collector"] ?? null), "pheanstalks", array());
        // line 43
        echo "    ";
        $this->displayBlock("table_pheanstalk", $context, $blocks);
        echo "

    <h2>Tubes</h2>
    ";
        // line 46
        $context["data"] = $this->getAttribute(($context["collector"] ?? null), "tubes", array());
        // line 47
        echo "    ";
        $this->displayBlock("table_tube", $context, $blocks);
        echo "

    <h2>Jobs</h2>
    ";
        // line 50
        $context["data"] = $this->getAttribute(($context["collector"] ?? null), "jobs", array());
        // line 51
        echo "    ";
        $this->displayBlock("table_jobs", $context, $blocks);
        echo "
";
    }

    // line 54
    public function block_table_pheanstalk($context, array $blocks = array())
    {
        // line 55
        echo "    <table>
        ";
        // line 56
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["data"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 57
            echo "            <tr>
                <th colspan=\"2\" style=\"text-align: center;vertical-align: middle;padding: 5px 0\">
                    ";
            // line 59
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "name", array()), "html", null, true);
            echo " ";
            if ($this->getAttribute($context["row"], "default", array())) {
                echo "(default)";
            }
            // line 60
            echo "                </th>
            </tr>
            <tbody>
                <tr>
                    <th>host</th>
                    <td>";
            // line 65
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "host", array()), "html", null, true);
            echo "</td>
                </tr>
                <tr>
                    <th>port</th>
                    <td>";
            // line 69
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "port", array()), "html", null, true);
            echo "</td>
                </tr>
                <tr>
                    <th>timeout</th>
                    <td>";
            // line 73
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "timeout", array()), "html", null, true);
            echo "</td>
                </tr>
                <tr>
                    <th>listening</th>
                    <td>";
            // line 77
            echo (($this->getAttribute($context["row"], "listening", array())) ? ("yes") : ("no"));
            echo "</td>
                </tr>
                <tr>
                    <th>default</th>
                    <td>";
            // line 81
            echo (($this->getAttribute($context["row"], "default", array())) ? ("yes") : ("no"));
            echo "</td>
                </tr>
                ";
            // line 83
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute($context["row"], "stats", array()));
            foreach ($context['_seq'] as $context["stat"] => $context["data"]) {
                // line 84
                echo "                <tr>
                    <th style=\"width: 150px\">";
                // line 85
                echo twig_escape_filter($this->env, $context["stat"], "html", null, true);
                echo "</th>
                    <td>";
                // line 86
                echo twig_escape_filter($this->env, $context["data"], "html", null, true);
                echo "</td>
                </tr>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['stat'], $context['data'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 89
            echo "            </tbody>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 91
        echo "    </table>
";
    }

    // line 94
    public function block_table_tube($context, array $blocks = array())
    {
        // line 95
        echo "    <table>
        ";
        // line 96
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["data"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 97
            echo "            <tr>
                <th colspan=\"2\" style=\"text-align: center;vertical-align: middle;padding: 5px 0\">
                    ";
            // line 99
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "name", array()), "html", null, true);
            echo " tube on '";
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "pheanstalk", array()), "html", null, true);
            echo "' pheanstalk
                </th>
            </tr>
            <tbody>
                <tr>
                    <th style=\"width: 150px\">pheanstalk</th>
                    <td>";
            // line 105
            echo twig_escape_filter($this->env, $this->getAttribute($context["row"], "pheanstalk", array()), "html", null, true);
            echo "</td>
                </tr>
                ";
            // line 107
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute($context["row"], "stats", array()));
            foreach ($context['_seq'] as $context["stat"] => $context["data"]) {
                // line 108
                echo "                    <tr>
                        <th style=\"width: 150px\">";
                // line 109
                echo twig_escape_filter($this->env, $context["stat"], "html", null, true);
                echo "</th>
                        <td>";
                // line 110
                echo twig_escape_filter($this->env, $context["data"], "html", null, true);
                echo "</td>
                    </tr>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['stat'], $context['data'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 113
            echo "            </tbody>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 115
        echo "    </table>
";
    }

    // line 118
    public function block_table_jobs($context, array $blocks = array())
    {
        // line 119
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["data"] ?? null));
        foreach ($context['_seq'] as $context["tube"] => $context["types"]) {
            // line 120
            echo "        <table>
            <th colspan=\"2\" style=\"text-align: center;vertical-align: middle;padding: 5px 0\">
                ";
            // line 122
            echo twig_escape_filter($this->env, $context["tube"], "html", null, true);
            echo "
            </th>
            ";
            // line 124
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($context["types"]);
            foreach ($context['_seq'] as $context["job_type"] => $context["job"]) {
                // line 125
                echo "                <tr>
                    <th rowspan=\"2\">Next ";
                // line 126
                echo twig_escape_filter($this->env, $context["job_type"], "html", null, true);
                echo "</th>
                    <td>Job ID: ";
                // line 127
                echo twig_escape_filter($this->env, $this->getAttribute($context["job"], "id", array()), "html", null, true);
                echo "</td>
                </tr>
                <tr>
                    <td>
                        <div style=\"float:left;width:100%;word-spacing:normal;word-wrap:break-word;max-width:720px;\">
                            Data: ";
                // line 132
                echo twig_escape_filter($this->env, $this->getAttribute($context["job"], "data", array()), "html", null, true);
                echo "
                        </div>
                    </td>
                </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['job_type'], $context['job'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 137
            echo "        </table>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['tube'], $context['types'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return "LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  339 => 137,  328 => 132,  320 => 127,  316 => 126,  313 => 125,  309 => 124,  304 => 122,  300 => 120,  295 => 119,  292 => 118,  287 => 115,  280 => 113,  271 => 110,  267 => 109,  264 => 108,  260 => 107,  255 => 105,  244 => 99,  240 => 97,  236 => 96,  233 => 95,  230 => 94,  225 => 91,  218 => 89,  209 => 86,  205 => 85,  202 => 84,  198 => 83,  193 => 81,  186 => 77,  179 => 73,  172 => 69,  165 => 65,  158 => 60,  152 => 59,  148 => 57,  144 => 56,  141 => 55,  138 => 54,  131 => 51,  129 => 50,  122 => 47,  120 => 46,  113 => 43,  111 => 42,  108 => 41,  105 => 40,  97 => 35,  93 => 34,  87 => 31,  84 => 30,  81 => 29,  72 => 23,  65 => 19,  58 => 15,  48 => 8,  44 => 7,  39 => 5,  36 => 4,  33 => 3,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "LeezyPheanstalkBundle:Profiler:pheanstalk.html.twig", "/var/www/symfony/vendor/leezy/pheanstalk-bundle/src/Resources/views/Profiler/pheanstalk.html.twig");
    }
}
