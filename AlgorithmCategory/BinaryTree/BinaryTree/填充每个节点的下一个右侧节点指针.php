<?php

use BinaryTree\Node;

/**
 * Definition for a Node.
 * class Node {
 *     function __construct($val = 0) {
 *         $this->val = $val;
 *         $this->left = null;
 *         $this->right = null;
 *         $this->next = null;
 *     }
 * }
 */
class PaddingEveryNodeSolution
{
    /**
     * @param Node $root
     * @return Node|array
     */
    public function connect(Node $root): Node|array
    {
        $this->def($root, NULL);
        return $root;
    }

    /**
     * @param Node $current
     * @param Node $next
     * @return void
     */
    private function def(Node $current, Node $next) {
        if (is_null($current)) {
            return NULL;
        }

        $current->next = $next;

        $this->def($current->left, $current->right);
        $this->def($current->right, is_null($current->next) ? NULL : $current->next->left);
    }
}