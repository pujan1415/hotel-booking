function fillMyDetails(user) {
    if (!user) return;

    // Split name
    const parts = user.name.split(' ');
    const fname = parts[0];
    const lname = parts.length > 1 ? parts.slice(1).join(' ') : '';

    const elFname = document.getElementById('fname');
    const elLname = document.getElementById('lname');
    const elEmail = document.getElementById('email');
    const elPhone = document.getElementById('phone');
    const elAddress = document.getElementById('address');

    if (elFname) elFname.value = fname;
    if (elLname) elLname.value = lname;
    if (elEmail) elEmail.value = user.email;
    if (elPhone) elPhone.value = user.phone;

    // Address not in DB, leaving blank or set generic
    if (elAddress && !elAddress.value) elAddress.value = "Kathmandu, Nepal";
}
