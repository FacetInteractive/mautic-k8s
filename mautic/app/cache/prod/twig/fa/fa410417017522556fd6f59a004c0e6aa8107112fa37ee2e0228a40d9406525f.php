<?php

/* OldSoundRabbitMqBundle:Collector:collector.html.twig */
class __TwigTemplate_acd030369c5d8280a41d62755ece7790ccf987bafa474b931e1b5423d532e545 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        // line 1
        $this->parent = $this->loadTemplate("WebProfilerBundle:Profiler:layout.html.twig", "OldSoundRabbitMqBundle:Collector:collector.html.twig", 1);
        $this->blocks = array(
            'toolbar' => array($this, 'block_toolbar'),
            'menu' => array($this, 'block_menu'),
            'panel' => array($this, 'block_panel'),
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
        echo "    ";
        if ($this->getAttribute(($context["collector"] ?? null), "publishedMessagesCount", array())) {
            // line 5
            echo "        ";
            ob_start();
            // line 6
            echo "            <img width=\"28\" height=\"28\" alt=\"RabbitMQ\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqlJREFUeNrsV01oE0EU/maz2U1tUFqtAY1QsaQVJKmHFjEF0R5E9CQIHhQF0UNBBL2IJ3tUevGiIOjFg0IpiErRXppD0VOpJCohQqWNYmzVtknaZtP9cWaTtKadDU3cmB76wUvCzJt5386+970JMQwDDMZQ3wmkf/bAIQKqAuz23yKHzkfAgRF/78Zo/9PlAZ3u4T95hbQEv6NMiOZnrL+bPO8dhKQBhACaDmzZ6kWL/xh2BGZWLyJDt+9g9M0pyEJuQKH+8bcDuBY6XC6B3A5fPnRiNg0sLALzC/Q7A/yYasfnsYPcVePhdtOH+Rb8J2IBVIDcCTicikmFFB4xT00QVe4qh5Q1ff72FyWlEgICaoxNApsENjgBqzIkgs4Z0+wjwOqahVica+DOZ1LuZQ0o+CvJxsqFiEeAUXt99ybi4QCVZx2GIdA+oSIRbcPvr3uKVrLf6V8C+o6OwBt4B21JtozI+oarPo0jl3ux06eIlo5OaonxLkzc7ypWQWp1HMJsPBIKYoxaKbDel6WmZWWcvXdDLOnozNt64VqnH20dmP3WXNsqEBxL1jnwLzA4r6fsJKwUyqraUvMRJA4x2wmwYL7OFwhefARRzpi6kEzswvCDq5ie7LDKJdG24PWNUVx6cgYeX7ZorsE7iYcXQtB1bsbZk4RMtOq2za0JzuBpjUKSLV+BfVVg6Py9dDZO/kMzYmrJLzc1d0TVJMBUMDXtQWTwwJq58MvTyGQsI4m2EVDmm/H43AD2H38FyUVzgXbM1FQTPtLru2itB/aVIdspNePDyLPrRSIklT5ne4WI16g27o3IcKycgEpbo4ZSyWovWCxNda4QaNr3CRLJXRZItR88f+57O4bNNCn8O0Ys1EavYNsLbbJ6BDQnZHcSrd2RYgI1wh8BBgAR2M5KdN1kRwAAAABJRU5ErkJggg==\" />
            <span class=\"sf-toolbar-status\">";
            // line 7
            echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "publishedMessagesCount", array()), "html", null, true);
            echo "</span>
        ";
            $context["icon"] = ('' === $tmp = ob_get_clean()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
            // line 9
            echo "        ";
            ob_start();
            // line 10
            echo "            <div class=\"sf-toolbar-info-piece\">
                <b>Messages</b>
                <span>";
            // line 12
            echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "publishedMessagesCount", array()), "html", null, true);
            echo "</span>
            </div>
        ";
            $context["text"] = ('' === $tmp = ob_get_clean()) ? '' : new Twig_Markup($tmp, $this->env->getCharset());
            // line 15
            echo "        ";
            $this->loadTemplate("WebProfilerBundle:Profiler:toolbar_item.html.twig", "OldSoundRabbitMqBundle:Collector:collector.html.twig", 15)->display(array_merge($context, array("link" => ($context["profiler_url"] ?? null))));
            // line 16
            echo "    ";
        }
    }

    // line 19
    public function block_menu($context, array $blocks = array())
    {
        // line 20
        echo "<span class=\"label\">
    <span class=\"icon\"><img alt=\"RabbitMQ\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqlJREFUeNrsV01oE0EU/maz2U1tUFqtAY1QsaQVJKmHFjEF0R5E9CQIHhQF0UNBBL2IJ3tUevGiIOjFg0IpiErRXppD0VOpJCohQqWNYmzVtknaZtP9cWaTtKadDU3cmB76wUvCzJt5386+970JMQwDDMZQ3wmkf/bAIQKqAuz23yKHzkfAgRF/78Zo/9PlAZ3u4T95hbQEv6NMiOZnrL+bPO8dhKQBhACaDmzZ6kWL/xh2BGZWLyJDt+9g9M0pyEJuQKH+8bcDuBY6XC6B3A5fPnRiNg0sLALzC/Q7A/yYasfnsYPcVePhdtOH+Rb8J2IBVIDcCTicikmFFB4xT00QVe4qh5Q1ff72FyWlEgICaoxNApsENjgBqzIkgs4Z0+wjwOqahVica+DOZ1LuZQ0o+CvJxsqFiEeAUXt99ybi4QCVZx2GIdA+oSIRbcPvr3uKVrLf6V8C+o6OwBt4B21JtozI+oarPo0jl3ux06eIlo5OaonxLkzc7ypWQWp1HMJsPBIKYoxaKbDel6WmZWWcvXdDLOnozNt64VqnH20dmP3WXNsqEBxL1jnwLzA4r6fsJKwUyqraUvMRJA4x2wmwYL7OFwhefARRzpi6kEzswvCDq5ie7LDKJdG24PWNUVx6cgYeX7ZorsE7iYcXQtB1bsbZk4RMtOq2za0JzuBpjUKSLV+BfVVg6Py9dDZO/kMzYmrJLzc1d0TVJMBUMDXtQWTwwJq58MvTyGQsI4m2EVDmm/H43AD2H38FyUVzgXbM1FQTPtLru2itB/aVIdspNePDyLPrRSIklT5ne4WI16g27o3IcKycgEpbo4ZSyWovWCxNda4QaNr3CRLJXRZItR88f+57O4bNNCn8O0Ys1EavYNsLbbJ6BDQnZHcSrd2RYgI1wh8BBgAR2M5KdN1kRwAAAABJRU5ErkJggg==\" /></span>
    <strong>RabbitMQ</strong>
    <span class=\"count\">
        <span>";
        // line 24
        echo twig_escape_filter($this->env, $this->getAttribute(($context["collector"] ?? null), "publishedMessagesCount", array()), "html", null, true);
        echo "</span>
    </span>
</span>
";
    }

    // line 29
    public function block_panel($context, array $blocks = array())
    {
        // line 30
        echo "    <h2>Messages</h2>
    ";
        // line 31
        if ($this->getAttribute(($context["collector"] ?? null), "publishedMessagesCount", array())) {
            // line 32
            echo "        <table>
            <thead>
                <tr>
                    <th scope=\"col\">Exchange</th>
                    <th scope=\"col\">Message body</th>
                </tr>
            </thead>
            <tbody>
                ";
            // line 40
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["collector"] ?? null), "publishedMessagesLog", array()));
            foreach ($context['_seq'] as $context["_key"] => $context["log"]) {
                // line 41
                echo "                <tr>
                    <td>";
                // line 42
                echo twig_escape_filter($this->env, $this->getAttribute($context["log"], "exchange", array()), "html", null, true);
                echo "</td>
                    <td>";
                // line 43
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["log"], "msg", array()), "body", array()), "html", null, true);
                echo "</td>
                </tr>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['log'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 46
            echo "            </tbody>
        </table>
    ";
        } else {
            // line 49
            echo "        <p>
            <em>No messages were sent.</em>
        </p>
    ";
        }
    }

    public function getTemplateName()
    {
        return "OldSoundRabbitMqBundle:Collector:collector.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  128 => 49,  123 => 46,  114 => 43,  110 => 42,  107 => 41,  103 => 40,  93 => 32,  91 => 31,  88 => 30,  85 => 29,  77 => 24,  71 => 20,  68 => 19,  63 => 16,  60 => 15,  54 => 12,  50 => 10,  47 => 9,  42 => 7,  39 => 6,  36 => 5,  33 => 4,  30 => 3,  11 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "OldSoundRabbitMqBundle:Collector:collector.html.twig", "/var/www/symfony/vendor/php-amqplib/rabbitmq-bundle/Resources/views/Collector/collector.html.twig");
    }
}
