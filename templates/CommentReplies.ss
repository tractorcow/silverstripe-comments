<% if $RepliesEnabled %>
	<div class="comment-replies-container">
		
		<a href="$ReplyLink">Reply to this comment</a>
	
		<div class="replies-holder">
			<% if $Replies %>
				<ul class="comments-list level-{$Level}">
					<% loop $Replies %>
						<li class="comment $EvenOdd<% if FirstLast %> $FirstLast <% end_if %> $SpamClass">
							<% include CommentsInterface_singlecomment %>
						</li>
					<% end_loop %>
				</ul>
				<% with $Replies %>
					<% include ReplyPagination %>
				<% end_with %>
			<% end_if %>
		</div>
	</div>
<% end_if %>
