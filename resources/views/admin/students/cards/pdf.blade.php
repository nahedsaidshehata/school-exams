<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 10mm; }

  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #0f172a;
  }

  table.page {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6mm 6mm;
  }

  td.card {
  width: 50%;
  height: 62mm; /* ✅ كان 55mm */
  background: #dceff0;
  border: 1px solid rgba(0,0,0,.15);
  border-radius: 12px;
  padding: 5mm;
  vertical-align: top;
  overflow: hidden;
  }

  /* --------- Layout تقسيم البطاقة --------- */
  .card-header {
    height: 8mm;            /* عنوان المدرسة */
    overflow: hidden;
    margin-bottom: 2mm;
  }

  .card-top {
  height: 19mm; /* ✅ كان 18mm */
  overflow: hidden;
  }

  .card-bottom {
  height: 32mm; /* ✅ كان 25mm */
  overflow: hidden;
  margin-top: 2mm;
  }

  .row { display: table; width: 100%; table-layout: fixed; }
  .cell { display: table-cell; vertical-align: top; }

  /* --------- النصوص --------- */

  /* اسم المدرسة (عنوان أعلى البطاقة) */
  .school-title {
  font-size: 11px;
  font-weight: 700;
  color: #0f172a;
  line-height: 14px;
  max-height: 28px;
  overflow: hidden;

  text-align: center; /* ✅ توسيط العنوان */
  }

  /* اسم الطالب (سطرين فقط) */
  .student-name {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    line-height: 14px;

    /* Dompdf-friendly clamp (سطرين) */
    max-height: 28px;     /* 2 lines * 14px */
    overflow: hidden;
  }

  /* الصف (سطر ثابت) */
  .grade {
    font-size: 10px;
    font-weight: 700;
    margin-top: 2px;
    color: #111827;
    white-space: nowrap;
    line-height: 13px; /* ✅ أمان إضافي */
  }

  .logo {
    width: 13mm;
    height: 13mm;
    border-radius: 50%;
    display: block;
  }

  /* --------- QR --------- */
  .qr-wrap {
    width: 27mm; /* مساحة مريحة داخل العمود */
  }

  .qr {
    width: 22mm;           /* صغّرناه شوية عشان ما يتقصش */
    height: 22mm;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 8px;
    padding: 1.5mm;        /* كان 2mm وبيزود الحجم */
    background: #fff;
    display: block;
    box-sizing: border-box; /* مهم جدًا عشان padding ما يكبرش الإطار */
  }

  .field {
    font-size: 10px;
    margin-top: 1.5mm;
    line-height: 13px;
  }

  .label { color: #475569; }
  .value { font-weight: bold; }
</style>
</head>

<body>

@foreach(collect($cards)->chunk(6) as $page)
  <table class="page">
    @php
      $rows = $page->chunk(2)->pad(3, collect());
    @endphp

    @foreach($rows as $row)
      <tr>
        @foreach($row->pad(2, null) as $c)
          @if($c)
            <td class="card">

              {{-- ✅ HEADER: School name at very top --}}
              <div class="card-header">
                <div class="school-title">
                  {{ $c['school'] }}
                </div>
              </div>

              {{-- ✅ TOP: Logo + Student name (2 lines max) + Grade always third line --}}
              <div class="card-top">
                <div class="row">
                  <div class="cell" style="width:16mm; padding-right:2mm;">
                    <img src="{{ public_path('images/logo.png') }}" class="logo">
                  </div>

                  <div class="cell">
                    <div class="student-name">{{ $c['student_name'] }}</div>
                    <div class="grade">Grade: {{ $c['grade'] }}</div>
                  </div>
                </div>
              </div>

              {{-- BOTTOM: QR + Username/Password --}}
              <div class="card-bottom">
                <div class="row">
                  <div class="cell qr-wrap" style="width:28mm;">
                    <img class="qr" src="{{ $c['qr'] }}">
                  </div>
                  <div class="cell">
                    <div class="field">
                      <strong>User name:</strong><br>
                      {{ $c['username'] }}
                    </div>
                    <div class="field">
                      <strong>Password:</strong><br>
                      {{ $c['password'] }}
                    </div>
                  </div>
                </div>
              </div>

            </td>
          @else
            <td></td>
          @endif
        @endforeach
      </tr>
    @endforeach
  </table>

  <div style="page-break-after: always;"></div>
@endforeach

</body>
</html>
