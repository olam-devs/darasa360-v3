<!-- Create Ticket Modal -->
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createTicketModalLabel">Create a Support Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('support.store') }}" method="POST" id="createTicketForm">
        @csrf
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-12">
              <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" placeholder="Brief description of the issue" value="{{ old('subject') }}" required>
              @error('subject')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="module_id" class="form-label">Module <span class="text-danger">*</span></label>
              <select class="form-select @error('module_id') is-invalid @enderror" id="module_id" name="module_id" required>
                <option value="">Select Module</option>
                @foreach($modules as $module)
                <option value="{{ $module->id }}" {{ old('module_id') == $module->id ? 'selected' : '' }}>
                  {{ $module->name }}
                </option>
                @endforeach
              </select>
              @error('module_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label for="sub_module" class="form-label">Sub Module</label>
              <input type="text" class="form-control @error('sub_module') is-invalid @enderror" id="sub_module" name="sub_module" placeholder="e.g. payroll, grades" value="{{ old('sub_module') }}">
              <small class="text-muted">Optional: specify the specific area</small>
              @error('sub_module')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label for="priority" class="form-label">Priority</label>
              <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority">
                <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }} selected>Medium</option>
                <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
              </select>
              @error('priority')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-12">
              <label for="description" class="form-label">Description <span class="text-danger">*</span></label>

              <!-- Toolbar -->
              <div class="btn-toolbar mb-2" role="toolbar">
                <div class="btn-group btn-group-sm me-2" role="group">
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('bold')" title="Bold">
                    <i class="bx bx-bold"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('italic')" title="Italic">
                    <i class="bx bx-italic"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('underline')" title="Underline">
                    <i class="bx bx-underline"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('strikeThrough')" title="Strike">
                    <i class="bx bx-strikethrough"></i>
                  </button>
                </div>
                <div class="btn-group btn-group-sm me-2" role="group">
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('insertUnorderedList')" title="Bullet List">
                    <i class="bx bx-list-ul"></i>
                  </button>
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('insertOrderedList')" title="Numbered List">
                    <i class="bx bx-list-ol"></i>
                  </button>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                  <button type="button" class="btn btn-outline-secondary" onclick="formatText('createLink')" title="Link">
                    <i class="bx bx-link"></i>
                  </button>
                </div>
              </div>

              <!-- Editor -->
              <div id="editor" contenteditable="true" class="form-control @error('description') is-invalid @enderror" style="min-height: 200px; max-height: 400px; overflow-y: auto;"
                placeholder="Describe the problem in detail...">{{ old('description') }}</div>
              <input type="hidden" name="description" id="descriptionInput" required>

              @error('description')
              <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
            <i class="bx bx-x me-1"></i>Cancel
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-send me-1"></i>Send
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  #editor {
    background: white;
    border: 1px solid #d9dee3;
    border-radius: 0.375rem;
    padding: 0.75rem;
  }

  [data-theme="dark"] #editor {
    background: var(--neutral-100);
    border-color: var(--border-color);
    color: var(--neutral-800);
  }

  #editor:focus {
    outline: none;
    border-color: #696cff;
    box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.1);
  }

  [data-theme="dark"] #editor:focus {
    border-color: #A78BFA;
    box-shadow: 0 0 0 0.25rem rgba(167, 139, 250, 0.2);
  }

  #editor[contenteditable]:empty:before {
    content: attr(placeholder);
    color: #a1acb8;
    font-style: italic;
  }

  .btn-toolbar .btn {
    border-radius: 0.375rem;
  }
</style>

<script>
  function formatText(command, value = null) {
    document.execCommand(command, false, value);
    document.getElementById('editor').focus();
  }

  // Save editor content to hidden input before submitting
  document.getElementById('createTicketForm').addEventListener('submit', function(e) {
    const editorContent = document.getElementById('editor').innerHTML;
    document.getElementById('descriptionInput').value = editorContent;

    // Validate that editor is not empty
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = editorContent;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';

    if (!textContent.trim()) {
      e.preventDefault();
      alert('Please provide a description for your ticket.');
      return false;
    }
  });

  // Update placeholder behavior
  document.getElementById('editor').addEventListener('paste', function(e) {
    e.preventDefault();
    const text = e.clipboardData.getData('text/plain');
    document.execCommand('insertText', false, text);
  });
</script>
