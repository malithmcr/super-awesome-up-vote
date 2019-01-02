/**
 * super-awesome-up-vote.js used only in admin.
 * Plugin Name: Post Images
 * Plugin URI: http://malith.pro
 * Description: Awesome image upload for wordpress posts.
 * Author: Malith Priyashan
 * Author URI: http://malith.pro
 * Version: 1.0.0
 * Text Domain: post-image
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
var superAwesomeUpVote = (function ($) {

    var upButton = $('#super-awesome-up');
    var upVote = $('#super-awesome-up-vote');
    var downButton = $('#super-awesome-down');
    var downVote = $('#super-awesome-down-vote');
    /**
     * Initializes method.
     */
    var init = function () {
        if(!localStorage.user_voted) {
            _voteUp();
            _voteDown();
        }
    };
    /**
     * _voteUp
     * @private
     */
    var _voteUp = function () {
            var upVoteStart = Number(upVote.text());
            upButton.on('click', function (e) {
                e.preventDefault();
            if(!localStorage.user_voted) {
                upVoteStart++;
                upVote.text(upVoteStart);

                //Send vote to backend
                _sendVote('vote_up')
            }
        });
    };

    /**
     * _voteDown
     * @private
     */
    var _voteDown = function () {
        var downVoteStart = Number(downVote.text());
            downButton.on('click', function (e) {
                e.preventDefault();
                if(!localStorage.user_voted) {
                    downVoteStart--;
                    downVote.text(downVoteStart);

                    //Send vote to backend
                    _sendVote('vote_down')
                }
            });
    };

    /**
     * _sendVote
     * @param vote = {obj}
     * @private
     */

    var _sendVote = function (vote, otherData) {
        $.post(
            window.location.origin + 'wp-admin/admin-ajax.php',
            {
                'action': 'add_votes',
                 'vote_type' : vote,
                 'postId': up_vote_post_id
            },
            function(response) {
                localStorage.setItem("user_voted", true);
            }
        );
    };

    //publicly accessible functions
    return {
        init: init
    };
}(jQuery));

/**
 * Run Super Awesome Javascript.
 */
superAwesomeUpVote.init();