import { Button } from '@/components/ui/button';
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle, DrawerTrigger } from '@/components/ui/drawer';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import BaseLayout from '@/layouts/baseLayout';
import { PaginationProps, SharedData } from '@/types';
import { Result } from '@/types/race';
import { Link, usePage } from '@inertiajs/react';

interface Props {
  results: PaginationProps<Result>;
}

export default function Results({ results }: Props) {
  const { userId } = usePage<SharedData>().props;

  return (
    <BaseLayout title="Results page" description="This is results page">
      <Link href={route('profile.show', { userId })}>
        <Button>Back</Button>
      </Link>
      <div className="mt-4 flex flex-col gap-y-12">
        <Table>
          <TableHeader>
            <TableRow className="bg-secondary *:text-center">
              <TableHead>#</TableHead>
              <TableHead>Quote ID</TableHead>
              <TableHead>Place</TableHead>
              <TableHead>WPM</TableHead>
              <TableHead>Accuracy</TableHead>
              <TableHead>Datetime</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {results.data.map((result, index) => (
              <TableRow className="*:py-3 *:text-center" key={result.id}>
                <TableCell>{results.from + index}</TableCell>
                <TableCell>
                  <Drawer>
                    <DrawerTrigger asChild>
                      <Button variant="outline">{result.quote?.id}</Button>
                    </DrawerTrigger>
                    <DrawerContent>
                      <div className="mx-auto w-full px-6 pb-10">
                        <DrawerHeader>
                          <DrawerTitle>Quote ID: {result.quote?.id}</DrawerTitle>
                        </DrawerHeader>
                        <div className="pt-4 text-justify text-3xl">{result.quote?.text}</div>
                      </div>
                    </DrawerContent>
                  </Drawer>
                </TableCell>
                <TableCell>{result.place === 'NC' ? result.place : `${result.place.slice(0, 1)} / ${result.total_players} `}</TableCell>
                <TableCell>{result.wpm}</TableCell>
                <TableCell>{result.accuracy_percentage}%</TableCell>
                <TableCell>{new Date(result.created_at).toLocaleString()}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>

        <Pagination>
          <PaginationContent>
            <PaginationItem>
              <PaginationPrevious href={results.links[0].url} className={!results.prev_page_url ? 'pointer-events-none opacity-70' : ' '} />
            </PaginationItem>
            {results.links.slice(1, results.links.length - 1).map((link) => (
              <PaginationItem key={link.url}>
                <PaginationLink href={link.url} isActive={link.active} className={link.active ? 'pointer-events-none' : ''}>
                  {link.label}
                </PaginationLink>
              </PaginationItem>
            ))}
            <PaginationItem>
              <PaginationNext
                href={results.links[results.links.length - 1].url}
                className={!results.next_page_url ? 'pointer-events-none opacity-70' : ' '}
              />
            </PaginationItem>
          </PaginationContent>
        </Pagination>
      </div>
    </BaseLayout>
  );
}
