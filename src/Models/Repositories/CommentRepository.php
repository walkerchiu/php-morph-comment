<?php

namespace WalkerChiu\MorphComment\Models\Repositories;

use Illuminate\Support\Facades\App;
use WalkerChiu\Core\Models\Forms\FormTrait;
use WalkerChiu\Core\Models\Repositories\Repository;
use WalkerChiu\Core\Models\Repositories\RepositoryTrait;
use WalkerChiu\MorphComment\Models\Repositories\CommentRepositoryTrait;

class CommentRepository extends Repository
{
    use FormTrait;
    use RepositoryTrait;
    use CommentRepositoryTrait;

    protected $entity;

    public function __construct()
    {
        $this->entity = App::make(config('wk-core.class.morph-comment.comment'));
    }

    /**
     * @param String  $code
     * @param Array   $data
     * @param Int     $page
     * @param Int     $nums per page
     * @param Boolean $is_enabled
     * @param Boolean $toArray
     * @return Array|Collection
     */
    public function list(String $code, Array $data, $page = null, $nums = null, $is_enabled = null, $toArray = true)
    {
        $this->assertForPagination($page, $nums);

        $entity = $this->entity;
        if ($is_enabled === true)      $entity = $entity->ofEnabled();
        elseif ($is_enabled === false) $entity = $entity->ofDisabled();

        $data = array_map('trim', $data);
        $records = $entity->with(['langs' => function ($query) use ($code) {
                                $query->ofCurrent()
                                      ->ofCode($code);
                             }])
                            ->when($data, function ($query, $data) {
                                return $query->unless(empty($data['id']), function ($query) use ($data) {
                                            return $query->where('id', $data['id']);
                                        })
                                        ->unless(empty($data['morph_type']), function ($query) use ($data) {
                                            return $query->where('morph_type', $data['morph_type']);
                                        })
                                        ->unless(empty($data['morph_id']), function ($query) use ($data) {
                                            return $query->where('morph_id', $data['morph_id']);
                                        })
                                        ->unless(empty($data['user_id']), function ($query) use ($data) {
                                            return $query->where('user_id', $data['user_id']);
                                        })
                                        ->unless(empty($data['score']), function ($query) use ($data) {
                                            return $query->where('score', $data['score']);
                                        })
                                        ->when(isset($data['is_private']), function ($query) use ($data) {
                                            return $query->where('is_private', $data['is_private']);
                                        })
                                        ->when(isset($data['is_highlighted']), function ($query) use ($data) {
                                            return $query->where('is_highlighted', $data['is_highlighted']);
                                        })
                                        ->unless(empty($data['edit_at']), function ($query) use ($data) {
                                            return $query->where('edit_at', $data['edit_at']);
                                        })
                                        ->unless(empty($data['subject']), function ($query) use ($data) {
                                            return $query->whereHas('langs', function($query) use ($data) {
                                                $query->ofCurrent()
                                                      ->where('key', 'subject')
                                                      ->where('value', 'LIKE', "%".$data['subject']."%");
                                            });
                                        })
                                        ->unless(empty($data['content']), function ($query) use ($data) {
                                            return $query->whereHas('langs', function($query) use ($data) {
                                                $query->ofCurrent()
                                                      ->where('key', 'content')
                                                      ->where('value', 'LIKE', "%".$data['content']."%");
                                            });
                                        });
                            })
                            ->orderBy('edit_at', 'DESC')
                            ->get()
                            ->when(is_integer($page) && is_integer($nums), function ($query) use ($page, $nums) {
                                return $query->forPage($page, $nums);
                            });
        if ($toArray) {
            $list = [];
            foreach ($records as $record) {
                if (config('wk-morph-comment.onoff.morph-address')) {
                    $address = ($record->user_id) ? $record->user->addresses('contact')->first()
                                                : $record->addresses('contact')->first();

                    $data = $record->toArray();
                    array_push($list,
                        array_merge($data, [
                            'subject' => $record->findLangByKey('subject'),
                            'content' => $record->findLangByKey('content'),
                            'address' => [
                                'phone' => $address->phone,
                                'email' => $address->email,
                                'name'  => $address->findLang($code, 'name')
                            ]
                        ])
                    );
                } else {
                    $data = $record->toArray();
                    array_push($list,
                        array_merge($data, [
                            'subject' => $record->findLangByKey('subject'),
                            'content' => $record->findLangByKey('content')
                        ])
                    );
                }
            }

            return $list;
        } else {
            return $records;
        }
    }

    /**
     * @param Comment $entity
     * @param Array|String $code
     * @return Array
     */
    public function show($entity, $code)
    {
        $data = [
            'id' => $entity ? $entity->id : '',
            'basic'    => [],
            'comments' => []
        ];

        if (empty($entity))
            return $data;

        $this->setEntity($entity);

        if (is_string($code)) {
            $data['basic'] = [
                  'morph_type'     => $entity->morph_type,
                  'morph_id'       => $entity->morph_id,
                  'user_id'        => $entity->user_id,
                  'score'          => $entity->score,
                  'options'        => $entity->options,
                  'is_private'     => $entity->is_private,
                  'is_highlighted' => $entity->is_highlighted,
                  'is_enabled'     => $entity->is_enabled,
                  'subject'        => $entity->findLang($code, 'subject'),
                  'content'        => $entity->findLang($code, 'content'),
                  'edit_at'        => $entity->edit_at,
                  'created_at'     => $entity->created_at,
                  'updated_at'     => $entity->updated_at
            ];
            if (config('wk-morph-comment.onoff.morph-address')) {
                $address = ($entity->user_id) ? $entity->user->addresses('contact')->first()
                                              : $entity->addresses('contact')->first();
                $data['basic'] = array_merge($data['basic'], [
                    'phone' => $address->phone,
                    'email' => $address->email,
                    'name'  => $address->findLang($code, 'name')
                ]);
            }

        } elseif (is_array($code)) {
            foreach ($code as $language) {
                $data['basic'][$language] = [
                    'morph_type'     => $entity->morph_type,
                    'morph_id'       => $entity->morph_id,
                    'user_id'        => $entity->user_id,
                    'score'          => $entity->score,
                    'options'        => $entity->options,
                    'is_private'     => $entity->is_private,
                    'is_highlighted' => $entity->is_highlighted,
                    'is_enabled'     => $entity->is_enabled,
                    'subject'        => $entity->findLang($language, 'subject'),
                    'content'        => $entity->findLang($language, 'content'),
                    'edit_at'        => $entity->edit_at,
                    'created_at'     => $entity->created_at,
                    'updated_at'     => $entity->updated_at
                ];
                if (config('wk-morph-comment.onoff.morph-address')) {
                    $address = ($entity->user_id) ? $entity->user->addresses('contact')->first()
                                                  : $entity->addresses('contact')->first();
                    $data['basic'][$language] = array_merge($data['basic'], [
                        'phone' => $address->phone,
                        'email' => $address->email,
                        'name'  => $address->findLang($language, 'name')
                    ]);
                }
            }
        }

        $data['comments'] = $this->getlistOfComments($entity);

        return $data;
    }
}
