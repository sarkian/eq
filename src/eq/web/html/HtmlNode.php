<?php

namespace eq\web\html;

    class HtmlNode
    {
        
        protected $name;
        protected $options = [];
        protected $content = [];
        
        public function __construct($name, $options = [], $content = [])
        {
            $this->name = $name;
            $this->options = $options;
            $this->content = $content;
        }

        public function attr($name, $value = null)
        {
            if($value === null)
                return $this->getAttribute($name);
            else
                $this->setAttribute($name, $value);
        }
        
        public function setAttribute($attr, $value)
        {
            $this->options[$attr] = $value;
        }
        
        public function getAttribute($attr)
        {
            return isset($this->options[$attr]) ? $this->options[$attr] : null;
        }

        public function getContents()
        {
            return $this->content;
        }
        
        public function append($child)
        {
            if(is_string($this->content)) {
                if($child instanceof HtmlNode)
                    $child = $child->render();
            }
            elseif(is_array($this->content)) {
                $this->content[] = $child;
            }
        }
        
        public function render()
        {
            if(is_string($this->content)) $content = $this->content;
            elseif(is_array($this->content)) {
                $content = '';
                foreach($this->content as $node) {
                    if($node instanceof HtmlNode) $content .= $node->render();
                    else $content .= $node;
                }
            }
            else $content = null;
            $content or $content = null;
            $res = Html::tag($this->name, $this->options, $content, $content ? false : true);
            if($content) $res .= '</'.$this->name.'>';
            return $res;
        }

        public function __toString()
        {
            return $this->render();
        }
        
    }
