<?php

namespace W4OS\Views;

/**
 * The stream of an avatar for the avatar itself: the fields that write a post, the key of its button, the actions its posts take,
 * and the requests of the engine that do them. Nobody else gets one, a manager neither: a profile shows its posts to the others.
 */
final readonly class PostsView
{
    /**
     * @param list<array<string,mixed>> $fields
     * @param list<ActionView> $actions
     * @param array<string,mixed> $posts the description of the stream (Contract::postsOf())
     */
    public function __construct(
        public string $avatar,
        public array $fields,
        public string $submit,
        public array $actions,
        public array $posts,
    ) {}

    /**
     * The stream of an avatar for whoever looks: null unless it is their own and they may write in it.
     *
     * @param array<string,mixed> $posts Contract::postsOf()
     * @param string $avatar the UUID of the avatar of the stream
     * @param ?string $self the UUID of the avatar of whoever looks
     * @param int $level their user level
     */
    public static function of(array $posts, string $avatar, ?string $self, int $level = 0): ?self
    {
        if ($self === null || $self !== $avatar || ! Access::allows((array) $posts['write'], $level, true)) {
            return null;
        }
        $actions = array_values(array_filter((array) $posts['actions'], fn (array $action) => ! isset($action['access']) || Access::allows($action['access'], $level, true)));

        return new self($avatar, (array) $posts['fields'], (string) $posts['submit'], array_map(fn (array $action) => ActionView::of($action), $actions), $posts);
    }

    /**
     * The request of the engine that writes a post: its method, its path, its document, whether it is asked under the grid.
     *
     * @return array{string, string, array<string,mixed>, bool}
     */
    public function write(string $content): array
    {
        $path = (string) preg_replace('#^/grids/\{grid\}#', '', (string) $this->posts['path']);

        return ['POST', str_replace('{id}', rawurlencode($this->avatar), $path), ['type' => 'posts', 'attributes' => ['content' => $content]], (bool) $this->posts['scoped']];
    }

    /**
     * The request of the engine that does an action on a post (`{id}` the avatar, `{post}` the post), null for an action it does
     * not take.
     *
     * @return ?array{string, string, array<string,mixed>, bool}
     */
    public function request(string $action, string $post): ?array
    {
        foreach ($this->actions as $known) {
            if ($known->name === $action) {
                return Rows::request($known->definition, ['uuid' => $this->avatar, 'post' => $post]);
            }
        }

        return null;
    }
}
