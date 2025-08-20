import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Player } from '@/types/race';

interface Props {
  players: Player[];
}

export default function LeaderBoard({ players }: Props) {
  return (
    <Sheet>
      <SheetTrigger asChild>
        <Button>Leaderboard</Button>
      </SheetTrigger>
      <SheetContent>
        <SheetHeader>
          <SheetTitle>Leaderboard</SheetTitle>
          <SheetDescription>Show players' scores and win rates</SheetDescription>
        </SheetHeader>

        <Tabs defaultValue="score">
          <TabsList>
            <TabsTrigger value="score">Score</TabsTrigger>
            <TabsTrigger value="win-rate">Win Rate</TabsTrigger>
          </TabsList>
          <TabsContent value="score">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>#</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead className="text-center">Score</TableHead>
                  <TableHead className="text-center">WPM</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {/* AVOID MUTATING THE ARRAY */}
                {[...players]
                  .sort((a, b) => b.score - a.score)
                  .map((player, index) => (
                    <TableRow key={player.id}>
                      <TableCell>{index + 1}</TableCell>
                      <TableCell>{player.name}</TableCell>
                      <TableCell className="text-center">{player.score}</TableCell>
                      <TableCell className="text-center">{player.averageWpm}</TableCell>
                    </TableRow>
                  ))}
              </TableBody>
            </Table>
          </TabsContent>
          <TabsContent value="win-rate">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>#</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead className="text-center">Played</TableHead>
                  <TableHead className="text-center">Won</TableHead>
                  <TableHead className="text-center">Win rate</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {[...players]
                  .sort((a, b) => b.racesWon / b.racesPlayed - a.racesWon / a.racesPlayed)
                  .map((player, index) => (
                    <TableRow key={player.id}>
                      <TableCell>{index + 1}</TableCell>
                      <TableCell>{player.name}</TableCell>
                      <TableCell className="text-center">{player.racesPlayed}</TableCell>
                      <TableCell className="text-center">{player.racesWon}</TableCell>
                      <TableCell className="text-center">
                        {player.racesPlayed < 1 ? 0 : Number(((player.racesWon / player.racesPlayed) * 100).toFixed(2))} %
                      </TableCell>
                    </TableRow>
                  ))}
              </TableBody>
            </Table>
          </TabsContent>
        </Tabs>
        <SheetFooter>
          <SheetClose asChild>
            <Button>Close</Button>
          </SheetClose>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
