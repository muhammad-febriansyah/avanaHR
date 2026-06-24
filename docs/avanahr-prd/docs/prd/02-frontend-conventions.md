# 02 — Frontend Conventions

> Aturan UI/UX wajib. Diturunkan dari permintaan eksplisit + konvensi standar AvanaHR. Semua modul mengikuti file ini.

## 1. Stack UI
Inertia 2 + React 18 + TypeScript · Tailwind v4 · **shadcn/ui** (Radix) · TanStack Table · react-day-picker · Sonner · lucide-react. **Poppins only, light mode only.** Token brand lihat `/CLAUDE.md §4`.

## 2. Pola Halaman (NO MODAL untuk CRUD)
Tiap resource = halaman Inertia terpisah:
```
Pages/<Module>/Index.tsx    # list + DataTable + tombol "Tambah"
Pages/<Module>/Create.tsx   # form create (Card)
Pages/<Module>/Edit.tsx     # form edit (Card)
Pages/<Module>/Show.tsx     # detail (read-only, opsional)
```
Modal **hanya** untuk: konfirmasi hapus (`ConfirmDialog`), quick action ringan (mis. approve cepat). Jangan taruh form CRUD penuh di modal.

## 3. Form (WAJIB)
- Seluruh form **dibungkus `FormCard`** (shadcn `Card`): `CardHeader` (judul + deskripsi singkat), `CardContent` (field), `CardFooter` (tombol Simpan/Batal, ikon lucide).
- Tiap field punya **`placeholder`** yang menjelaskan ekspektasi input.
- Field wajib menampilkan **`*` merah** di label via `<RequiredMark/>`.
- Error validasi **inline** di bawah field, **Bahasa Indonesia** (dari Laravel FormRequest). Gunakan `useForm` Inertia + `errors`.
- Tombol submit disable + spinner saat `processing`. Setelah sukses → redirect + Sonner toast.

```tsx
<FormCard title="Tambah Karyawan" description="Lengkapi data wajib karyawan baru.">
  <div className="grid gap-5 md:grid-cols-2">
    <Field label="Nama Lengkap" required error={errors.name}>
      <Input placeholder="mis. Budi Santoso" value={data.name}
             onChange={e=>setData('name', e.target.value)} />
    </Field>
    <Field label="Tanggal Masuk" required error={errors.join_date}>
      <DatePicker value={data.join_date} onChange={v=>setData('join_date', v)}
                  placeholder="Pilih tanggal" />
    </Field>
    <Field label="Gaji Pokok" required error={errors.basic_salary}>
      <RupiahInput value={data.basic_salary} onChange={v=>setData('basic_salary', v)}
                   placeholder="0" />
    </Field>
  </div>
  <FormFooter onCancel={...} submitting={processing} />
</FormCard>
```
`Field` = wrapper Label (+`RequiredMark` bila `required`) + control + pesan error.

## 4. List = shadcn DataTable (TanStack) — WAJIB
Ref: https://ui.shadcn.com/docs/components/radix/data-table

`<DataTable/>` reusable dengan:
- **Server-side** pagination, sorting, filtering (kirim `page/per_page/sort/dir/filters` ke Laravel; backend `paginate()`/`cursorPaginate()`).
- Column visibility toggle, row selection (untuk bulk action), **search debounce 300ms**.
- Empty state (`EmptyState`) & loading skeleton. Default `per_page = 15`.
- Tiap resource: `Pages/<Module>/columns.tsx` (definisi kolom + cell formatter: `Money`, `formatDateID`, `StatusBadge`, action menu).
- Aksi baris via dropdown (Lihat/Ubah/Hapus) dengan ikon lucide. Hapus → `ConfirmDialog`.

## 5. Notifikasi = Sonner
- `<Toaster/>` dipasang di root layout.
- `useFlashToast()` membaca Inertia shared props `flash.success | flash.error | flash.info` → panggil `toast.success/error/info`.
- Backend set `->with('success', 'Data tersimpan.')` setelah redirect. Pesan singkat, Bahasa Indonesia.

## 6. Uang = Rupiah
- Display: `formatRupiah(amount)` → `Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0})` → `Rp 10.000.000`. Komponen `<Money value={...}/>`, rata kanan.
- Input: `<RupiahInput/>` (mask ribuan, simpan number murni). Backend simpan **BIGINT rupiah**.

## 7. Tanggal = Date Picker
- Semua field tanggal pakai `<DatePicker/>` (shadcn + react-day-picker). Tidak ada input teks tanggal manual.
- Filter periode/report pakai `<DateRangePicker/>`.
- Display `formatDateID(date)` → `23 Jun 2026`; datetime → `23 Jun 2026 14:30 WIB`. Simpan UTC, tampil WIB.

## 8. Ikon & Layout
- **Setiap button** ada leading icon lucide-react (mis. `Plus` Tambah, `Save` Simpan, `Trash2` Hapus, `Check` Setujui, `Download` Export).
- `PageHeader`: judul + breadcrumb + tombol aksi kanan atas.
- Grid form 1–2 kolom (responsive `md:grid-cols-2`). Spasi konsisten (`gap-5`).
- Status pakai `<StatusBadge/>` (warna sesuai enum: draft/pending/approved/rejected/locked, dst).

## 9. Performa Frontend (WAJIB)
- Inertia **partial reload** (`router.reload({ only:['rows'] })`) untuk refresh tabel tanpa reload penuh.
- **Deferred props** / lazy untuk data berat; `<WhenVisible/>` untuk konten below-fold.
- **Prefetch on hover** untuk navigasi utama; code-split per route.
- Skeleton loader saat fetch; jangan blok seluruh UI.
- Jangan pernah load seluruh tabel ke client — selalu paginate server-side.

## 10. Aksesibilitas & Mobile-web
- Label terhubung ke input (`htmlFor`), focus ring jelas (`--ring`), kontras cukup.
- Layout responsif untuk mobile-web (Lite) & mode Kiosk fullscreen.
- Konfirmasi destruktif selalu eksplisit.

## 11. Komponen yang harus ada (recap)
`FormCard`, `Field`, `RequiredMark`, `FormFooter`, `DataTable` (+`columns.tsx` per resource), `RupiahInput`, `Money`, `DatePicker`, `DateRangePicker`, `PageHeader`, `StatusBadge`, `EmptyState`, `ConfirmDialog`, `useFlashToast`, `EffectiveDateBadge`. Util di `lib/format.ts`: `formatRupiah`, `formatDateID`, `formatDateTimeID`.
