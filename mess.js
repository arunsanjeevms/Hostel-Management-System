$(document).ready(function(){
    // Token History is loaded server-side, no need for AJAX call

    // Special Meals
    function loadSpecialMeals(){
        // This function would load special meals data
        console.log('Loading special meals data...');
        // Implementation would go here
    }

    // Monthly Bill
    function loadMonthlyBill(){
        // This function would load monthly bill data
        console.log('Loading monthly bill data...');
        // Implementation would go here
    }

    // Tab-based content loading - load data when tabs are activated
    $('#special-meals-tab').on('shown.bs.tab', function (e) {
        loadSpecialMeals();
    });

    $('#monthly-bill-tab').on('shown.bs.tab', function (e) {
        loadMonthlyBill();
    });

    // Admin interface tab handlers with consistent styling
    $('#dailymenu-main-tab').on('shown.bs.tab', function (e) {
        // Initialize DataTable for daily menu if not already initialized
        if ($('#messMenuTable').length > 0 && !$.fn.DataTable.isDataTable('#messMenuTable')) {
            $('#messMenuTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        }
    });

    $('#specialtoken-main-tab').on('shown.bs.tab', function (e) {
        // Initialize DataTable for special tokens if not already initialized
        if ($('#specialtokenEnableTable').length > 0 && !$.fn.DataTable.isDataTable('#specialtokenEnableTable')) {
            $('#specialtokenEnableTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        }
    });

    $('#tokens-main-tab').on('shown.bs.tab', function (e) {
        // Initialize DataTable for view tokens if not already initialized
        if ($('#messTokensTable').length > 0 && !$.fn.DataTable.isDataTable('#messTokensTable')) {
            $('#messTokensTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        }
    });

    // Request special token - using event delegation to handle dynamically created buttons
    $(document).on('click', '.request-btn', function(){
        let menu_id = $(this).data('menu-id');
        let btn = $(this);
        
        // Disable button to prevent double clicks
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Requesting...');
        
        $.post('./mess-php/request_special_token.php', {
            menu_id: menu_id
        }, function(response){
            // Handle both string and JSON responses
            let resp;
            if (typeof response === 'string') {
                try {
                    resp = JSON.parse(response);
                } catch (e) {
                    // If parsing fails, show the raw response for debugging
                    Swal.fire('Error', 'Invalid response from server: ' + response.substring(0, 200) + '...', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
                    return;
                }
            } else {
                resp = response;
            }
            
            if(resp.status){
                Swal.fire('Success', resp.msg, 'success');
                // Update button to show "Special Token Taken" in yellow
                btn.removeClass('btn-success').addClass('btn-warning')
                   .html('<i class="fas fa-check"></i> Special Token Taken')
                   .prop('disabled', true)
                   .css({
                       'padding': '0.5rem 1rem',
                       'font-size': '0.9rem',
                       'min-width': '120px'
                   });
                
                // Add the new token to the history table immediately without refreshing
                if (resp.token_data) {
                    // Format the date for display
                    const createdAt = new Date(resp.token_data.created_at);
                    const formattedDate = createdAt.toLocaleDateString('en-GB', { 
                        day: '2-digit', 
                        month: 'short', 
                        year: 'numeric' 
                    });
                    
                    // Add to Special Token History table (second table in special-meals tab)
                    let historyRow = `
                        <tr>
                            <td>${resp.token_data.token_date}</td>
                            <td>${resp.token_data.meal_type}</td>
                            <td>${resp.token_data.menu.replace(/\n/g, '<br>')}</td>
                            <td>₹${parseFloat(resp.token_data.special_fee).toFixed(2)}</td>
                            <td>${resp.token_data.created_at}</td>
                        </tr>
                    `;
                    
                    // Find the history table body and add the new row at the top
                    $('#special-meals .table-container').eq(1).find('tbody').prepend(historyRow);
                    
                    // Update monthly bill details table
                    let monthlyRow = `
                        <tr>
                            <td>${formattedDate}</td>
                            <td>${resp.token_data.meal_type}</td>
                            <td>${resp.token_data.menu.replace(/\n/g, '<br>')}</td>
                            <td>₹${parseFloat(resp.token_data.special_fee).toFixed(2)}</td>
                        </tr>
                    `;
                    
                    // Add to monthly bill details (before the total row)
                    $('#monthly-bill .table-container').find('tbody tr.table-primary').before(monthlyRow);
                    
                    // Update the monthly total
                    let currentTotal = parseFloat($('#monthly-bill .table-container').find('tbody tr.table-primary td:last strong').text().replace('₹', '')) || 0;
                    let newTotal = currentTotal + parseFloat(resp.token_data.special_fee);
                    $('#monthly-bill .table-container').find('tbody tr.table-primary td:last strong').text('₹' + newTotal.toFixed(2));
                }
            } else {
                Swal.fire('Error', resp.msg, 'error');
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
            }
        }).fail(function(xhr, status, error) {
            // Show detailed error information
            let errorMessage = 'Failed to connect to server';
            if (xhr.responseText) {
                errorMessage += ': ' + xhr.responseText.substring(0, 200) + '...';
            } else {
                errorMessage += ': ' + error;
            }
            Swal.fire('Error', errorMessage, 'error');
            btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
        });
    });
    
    // Remove the download button click handler since we're not using PDFs anymore
    // Handle download button clicks - using event delegation
    /*$(document).on('click', '.download-btn', function() {
        let tokenId = $(this).data('token-id');
        
        // Fetch token data and generate PDF
        $.getJSON('./mess-php/get_token_data.php?token_id=' + tokenId, function(response) {
            if (response.status) {
                // Generate PDF using jsPDF
                generateTokenPDF(response.data);
            } else {
                Swal.fire('Error', response.msg, 'error');
            }
        }).fail(function() {
            Swal.fire('Error', 'Failed to fetch token data', 'error');
        });
    });*/
    
    // Function to generate PDF using jsPDF - remove this since we're not using PDFs
    /*function generateTokenPDF(tokenData) {
        // Create a new jsPDF instance
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Set font and add content
        doc.setFontSize(18);
        doc.text('Special Mess Token', 105, 20, null, null, 'center');
        
        // Add token details
        let y = 40; // Adjusted starting position since we removed a line
        doc.setFontSize(12);
        
        doc.text('Token ID: ' + tokenData.token_id, 20, y);
        y += 10;
        
        doc.text('Roll Number: ' + tokenData.roll_number, 20, y);
        y += 10;
        
        doc.text('Student Name: ' + tokenData.student_name, 20, y);
        y += 10;
        
        doc.text('Meal Type: ' + tokenData.meal_type, 20, y);
        y += 10;
        
        // Split menu items if they are too long
        const menuItems = doc.splitTextToSize('Items: ' + tokenData.menu, 170);
        doc.text(menuItems, 20, y);
        y += (menuItems.length * 10);
        
        doc.text('Fee: ₹' + parseFloat(tokenData.special_fee).toFixed(2), 20, y);
        y += 10;
        
        doc.text('Token Date: ' + tokenData.token_date, 20, y);
        y += 10;
        
        doc.text('Requested At: ' + tokenData.created_at, 20, y);
        y += 10;
        
        doc.setTextColor(255, 0, 0); // Red color
        doc.text('Expiry Date: ' + tokenData.token_date, 20, y);
        doc.setTextColor(0, 0, 0); // Reset to black color
        y += 20;
        
        // Add footer
        doc.setFontSize(10);
        doc.text('This is a computer-generated token and does not require signature.', 105, y, null, null, 'center');
        y += 10;
        doc.text('Generated on ' + new Date().toLocaleString(), 105, y, null, null, 'center');
        
        // Save the PDF
        doc.save('SpecialToken_' + tokenData.roll_number + '_ID_' + tokenData.token_id + '.pdf');
    }*/

    // Initialize DataTables for admin interface when the page loads with consistent styling
    if ($('#messMenuTable').length > 0) {
        $('#messMenuTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    }
    if ($('#specialtokenEnableTable').length > 0) {
        $('#specialtokenEnableTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    }
    if ($('#messTokensTable').length > 0) {
        $('#messTokensTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    }
});