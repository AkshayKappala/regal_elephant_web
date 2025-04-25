<div class="container">
    <h1>Cart</h1>
    <div class="row gx-5"> <!-- Added gx-5 for horizontal gap -->
        <!-- Left Column: Cart Items and Summary -->
        <div class="col-md-6">
            <div id="cart-items-display">
                <!-- Cart items table will be rendered here by main.js -->
            </div>
            <div id="cart-summary" class="mt-4">
                <!-- Cart summary will be rendered here by main.js -->
            </div>
        </div>

        <!-- Right Column: Customer Details and Tip -->
        <div class="col-md-6">
            <!-- Moved H3 inside a structure that aligns with the form -->
            <div class="row mb-4">
                 <div class="col-sm-9 offset-sm-3"> <!-- Offset to align with input fields -->
                    <h3>Your Details</h3>
                 </div>
            </div>
            <div id="customer-details-section">
                <form id="customer-details-form">
                    <!-- Use Bootstrap row/col for inline labels and inputs -->
                    <div class="row mb-3 align-items-center">
                        <label for="customer-name" class="col-sm-3 col-form-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="customer-name" required>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <label for="customer-mobile" class="col-sm-3 col-form-label">Mobile</label>
                        <div class="col-sm-9">
                            <!-- Added pattern for 10 digit mobile number -->
                            <input type="tel" class="form-control" id="customer-mobile" required pattern="\d{10}" title="Please enter a 10-digit mobile number">
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <label for="customer-email" class="col-sm-3 col-form-label">Email</label>
                        <div class="col-sm-9">
                            <!-- Removed pattern attribute -->
                            <input type="email" class="form-control" id="customer-email" title="Please enter a valid email address">
                        </div>
                    </div>
                    <!-- Removed Tax Percentage Input Field -->
                    <div class="row mb-3 align-items-center">
                        <label for="tip-amount" class="col-sm-3 col-form-label">Tip (&#8377;)</label>
                        <div class="col-sm-9">
                            <!-- Changed step to allow decimals, kept min="0" -->
                            <input type="number" class="form-control" id="tip-amount" step="0.01" min="0">
                        </div>
                    </div>
                    <!-- Placeholder for Place Order button -->
                     <div id="place-order-button-container" class="mt-4 pt-3 text-end"> <!-- Removed border classes, added text-end -->
                        <!-- Place Order button will be appended here by JS -->
                     </div>
                </form>
            </div>
        </div>
    </div>
</div>
