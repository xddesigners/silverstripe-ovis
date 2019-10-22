<div class="row column ovis-filters">
    $Filters
</div>
<div class="row small-up-1 medium-up-2 large-up-3 ovis-presentations">
    <% if $PaginatedPresentations %>
        <% loop $PaginatedPresentations %>
            <div class="column column-block">
                <div class="card">
                    <div class="card-divider">
                        <h4>$Title</h4>
                        <p class="subtitle">$PriceNice</p>
                    </div>
                    <img src="$DefaultImage.FocusFill(400,300).Link">
                    <div class="card-section">
                        $Description.Summary(15, 25)
                    </div>
                    <div class="card-section">
                        <a href="$Link" class="button">Read more</a>
                    </div>
                </div>
            </div>
        <% end_loop %>
    <% else %>
        <h3><%t XD\Ovis\Models\OvisPage.NoPresentations 'No presentations found' %></h3>
    <% end_if %>
</div>
<% with $PaginatedPresentations %>
    <div class="row column">
        <% include XD\Ovis\Includes\Pagination %>
    </div>
<% end_with %>