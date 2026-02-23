import React from 'react';

const PostsIndex = ({ posts }) => {
    return (
        <div>
            <h1>Posts</h1>
            {posts.map((post) => (
                <div key={post.id}>
                    <h2>{post.title[window.currentLang]}</h2>
                    <p>{post.content[window.currentLang]}</p>
                </div>
            ))}
        </div>
    );
};

export default PostsIndex;  