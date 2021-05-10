<?php

namespace WalkerChiu\MorphComment\Models\Services;

use Illuminate\Support\Facades\App;
use WalkerChiu\Core\Models\Services\CheckExistTrait;

class CommentService
{
    use CheckExistTrait;

    protected $repository;

    public function __construct()
    {
        $this->repository = App::make(config('wk-core.class.morph-comment.commentRepository'));
    }

    /**
     * @param Comment|String $source
     * @param User           $viewer
     * @param User           $author
     * @return Int
     */
    public function countComments($source, $viewer = null, $author = null)
    {
        $comment_id = is_string($source) ? $source : $source->id;

        $data = [
            'morph_type' => config('wk-core.class.morph-comment.comment'),
            'morph_id'   => $comment_id
        ];

        if ($author)
            $data['user_id'] = $author->id;

        return App::make(config('wk-core.class.morph-comment.commentRepository'))
                    ->whereByArray($data)
                    ->count();
    }
}
