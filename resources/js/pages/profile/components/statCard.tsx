export default function StatCard({ title, stat }: { title: string; stat: string | number }) {
  return (
    <div className="flex flex-col gap-y-2">
      <div className="text-xl text-muted-foreground">{title}</div>
      <div className="ml-1 text-3xl text-secondary-foreground">{stat}</div>
    </div>
  );
}
