<% if $MoreThanOnePage %>
    <div class="ovis-pagination">
        <% if $NotFirstPage %>
            <span class="ovis-pagination__item ovis-pagination__item--prev">
					<a href="$PrevLink" title="Toon de vorige pagina">Previous</a>
				</span>
        <% end_if %>

        <% loop $PaginationSummary(4) %>
            <% if $CurrentBool %>
                <span class="ovis-pagination__item ovis-pagination__item--current"><span>$PageNum</span></span>
            <% else %>
                <% if $Link %>
                    <span class="ovis-pagination__item">
							<a href="$Link" title="Toon pagina {pageNum}">$PageNum</a>
						</span>
                <% end_if %>
            <% end_if %>
        <% end_loop %>

        <% if $NotLastPage %>
            <span class="ovis-pagination__item site-pagination__item--next">
					<a href="$NextLink" title="Toon de volgende pagina">Next</a>
				</span>
        <% end_if %>
    </div>
<% end_if %>



