<form action="{{ url('charge') }}" method="post">
    {{ csrf_field() }}
    <p><input type="text" name="amount" placeholder="Enter Amount" /></p>
    <p><input type="text" name="cc_number" placeholder="Card Number" /></p>
    <p><input type="text" name="expiry_month" placeholder="Month" /></p>
    <p><input type="text" name="expiry_year" placeholder="Year" /></p>
    <p><input type="text" name="cvv" placeholder="CVV" /></p>
    <input type="submit" name="submit" value="Submit" />
</form>