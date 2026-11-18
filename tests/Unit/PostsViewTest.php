<?php

use W4OS\Views\Contract;
use W4OS\Views\PostsView;

describe('the posts of an avatar', function () {
    $api = new Contract(contract());

    test('are described by the operation that writes one: its fields, its button, its actions and who may do them', function () use ($api) {
        $posts = $api->postsOf();

        expect($posts['fields'][0] ?? [])->toMatchArray(['name' => 'content', 'type' => 'text', 'maxLength' => 1000, 'required' => true, 'label' => 'post_label'])
            ->and($posts['submit'])->toBe('action_post')
            ->and(array_column($posts['actions'], 'name'))->toBe(['deletePost'])
            ->and($posts['actions'][0]['access'])->toMatchArray(['self' => true, 'operation' => 'deleteAvatarPost'])
            ->and($posts['write'])->toMatchArray(['self' => true, 'operation' => 'createAvatarPost'])
            ->and((new Contract([]))->postsOf())->toBeNull();

        return $posts;
    });

    test('are written and deleted by the avatar itself only', function (array $posts) {
        $own = PostsView::of($posts, 'u1', 'u1');

        expect($own)->not->toBeNull()
            ->and(array_column($own->fields, 'name'))->toBe(['content'])
            ->and($own->submit)->toBe('action_post')
            ->and(array_column($own->actions, 'name'))->toBe(['deletePost'])
            ->and($own->actions[0]->ask)->toBe('ask_delete_post')
            // Nobody else, a god neither, nor a visitor
            ->and(PostsView::of($posts, 'u1', 'u2', 200))->toBeNull()
            ->and(PostsView::of($posts, 'u1', null))->toBeNull();

        return $own;
    })->depends('are described by the operation that writes one: its fields, its button, its actions and who may do them');

    test('give the requests of the engine that write and delete one', function (PostsView $own) {
        expect($own->write('Hello'))->toBe(['POST', '/avatars/u1/posts', ['type' => 'posts', 'attributes' => ['content' => 'Hello']], true])
            ->and($own->request('deletePost', 'p 7'))->toMatchArray([0 => 'DELETE', 1 => '/avatars/u1/posts/p%207', 3 => true])
            ->and($own->request('nothing', 'p7'))->toBeNull();
    })->depends('are written and deleted by the avatar itself only');
});
