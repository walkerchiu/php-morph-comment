<?php

namespace WalkerChiu\MorphComment\Models\Entities;

use WalkerChiu\Core\Models\Entities\Lang;

class CommentLang extends Lang
{
    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('wk-core.table.morph-comment.comments_lang');

        parent::__construct($attributes);
    }
}
