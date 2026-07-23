<!-- Add Building Modal -->
<div class="modal fade" id="addBuildingModal" tabindex="-1" aria-labelledby="addBuildingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('admin.building.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="addBuildingModalLabel">Add New Building</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label for="building_name" class="form-label">Building Name *</label>
            <input type="text" class="form-control" name="building_name" id="building_name" required>
          </div>

          <div class="col-md-6">
            <label for="management_email" class="form-label">Management Email</label>
            <input type="email" class="form-control" name="management_email" id="management_email">
          </div>

          <div class="col-md-6">
            <label for="security_contact" class="form-label">Security Contact</label>
            <input type="text" class="form-control" name="security_contact" id="security_contact">
          </div>

          <div class="col-md-6">
            <label for="gas_provider" class="form-label">Gas Provider</label>
            <input type="text" class="form-control" name="gas_provider" id="gas_provider">
          </div>

          <div class="col-md-12">
            <label for="address" class="form-label">Address *</label>
            <input type="text" class="form-control" name="address" id="address" required>
          </div>

          <div class="col-md-4">
            <label for="city" class="form-label">City</label>
            <input type="text" class="form-control" name="city" id="city">
          </div>

          <div class="col-md-4">
            <label for="state" class="form-label">State</label>
            <input type="text" class="form-control" name="state" id="state">
          </div>

          <div class="col-md-4">
            <label for="country" class="form-label">Country</label>
            <input type="text" class="form-control" name="country" id="country">
          </div>

          <div class="col-md-6">
            <label for="google_map_link" class="form-label">Google Map Link</label>
            <input type="url" class="form-control" name="google_map_link" id="google_map_link">
          </div>

          <div class="col-md-6">
            <label for="year_built" class="form-label">Year Built</label>
            <input type="number" class="form-control" name="year_built" id="year_built" min="1800" max="{{ date('Y') }}">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Building</button>
        </div>
      </form>
    </div>
  </div>
</div>
